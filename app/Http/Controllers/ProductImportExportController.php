<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductBarcode;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductImportExportController extends Controller
{
    public function exportCsv(Request $request): StreamedResponse
    {
        $user = $request->user();
        if (!$user || !$user->hasRole('admin')) {
            abort(403, 'No autorizado');
        }

        $fileName = 'productos_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8 para que Excel en Windows reconozca acentos
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'id',
                'category_name',
                'name',
                'description',
                'price',
                'is_active',
                'image_path',
            ]);

            Product::with('category')->orderBy('id')->chunk(200, function ($products) use ($handle) {
                foreach ($products as $product) {
                    fputcsv($handle, [
                        $product->id,
                        $product->category?->name,
                        $product->name,
                        $product->description,
                        $product->price,
                        $product->is_active ? 1 : 0,
                        $product->image_path,
                    ]);
                }
            });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importForm(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->hasRole('admin')) {
            abort(403, 'No autorizado');
        }

        return view('products.import');
    }

    public function import(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->hasRole('admin')) {
            abort(403, 'No autorizado');
        }

        $data = $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        $file = $data['file'];
        $path = $file->getRealPath();

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return back()->withErrors(['file' => 'No se pudo leer el archivo subido.']);
        }

        // En esta instalación los archivos provienen de Excel en español,
        // que usa ';' como separador de columnas. En lugar de usar fgetcsv,
        // que se está comportando de forma inesperada con este formato,
        // parseamos manualmente cada línea con explode(';').

        $headerLine = fgets($handle);
        if ($headerLine === false) {
            fclose($handle);
            return back()->withErrors(['file' => 'El archivo CSV está vacío o no tiene cabecera.']);
        }

        $headerLine = rtrim($headerLine, "\r\n");

        // Detectar delimitador: preferir ';', pero si solo hay una columna, probar ','.
        $delimiter = ';';
        $columns = array_map('trim', explode($delimiter, $headerLine));
        if (count($columns) < 2) {
            $delimiter = ',';
            $columns = array_map('trim', explode($delimiter, $headerLine));
        }

        // Normalizar nombres de columnas: quitar posibles comillas y espacios raros
        $columns = array_map(function ($col) {
            // Eliminar comillas simples/dobles al inicio/fin y espacios Unicode
            $col = trim($col);
            $col = preg_replace('/^["\']+|["\']+$/u', '', $col);
            // Normalizar espacios en blanco
            $col = preg_replace('/\s+/u', ' ', $col);
            return trim($col);
        }, $columns);

        \Log::info('IMPORT DEBUG header', ['columns' => $columns]);

        // Limpiar posible BOM UTF-8 en la primera columna (suele aparecer como "ï»¿id")
        if (isset($columns[0])) {
            $columns[0] = preg_replace('/^\xEF\xBB\xBF/', '', $columns[0]);
        }

        // Columnas requeridas mínimas (las demás son opcionales)
        // price se permite vacío por fila; si falta se asume 0.0
        $expected = ['category_name', 'name', 'price'];
        foreach ($expected as $col) {
            if (!in_array($col, $columns, true)) {
                fclose($handle);
                return back()->withErrors(['file' => 'Falta la columna requerida: ' . $col]);
            }
        }

        $index = array_flip($columns);
        $updated = 0;
        $created = 0;

        while (($line = fgets($handle)) !== false) {
            $line = rtrim($line, "\r\n");
            if ($line === '') {
                continue; // saltar filas vacías
            }

            $row = explode($delimiter, $line);

            // En el CSV legado, la primera columna se llama "id" pero
            // la vamos a interpretar como SKU/código de producto
            // y también como posible barcode numérico.
            $legacyCode = isset($index['id']) ? trim($row[$index['id']] ?? '') : '';
            $categoryName = trim($row[$index['category_name']] ?? '');
            $name = trim($row[$index['name']] ?? '');
            $description = isset($index['description']) ? trim($row[$index['description']] ?? '') : '';
            $sku = isset($index['sku']) ? trim($row[$index['sku']] ?? '') : '';
            if ($sku === '' && $legacyCode !== '') {
                $sku = $legacyCode;
            }
            $price = trim($row[$index['price']] ?? '');
            // Si hay columna is_active pero la celda está vacía, se asumirá activo (1).
            // Aceptar también valores típicos de Excel: 1/0, TRUE/FALSE, YES/NO, SI/NO, SÍ/NO, etc.
            $isActiveRaw = isset($index['is_active']) ? trim((string) ($row[$index['is_active']] ?? '')) : '';
            $imagePath = isset($index['image_path']) ? trim($row[$index['image_path']] ?? '') : '';

            // Nuevos campos opcionales
            $cost = isset($index['cost']) ? trim($row[$index['cost']] ?? '') : '';
            $markupPercent = isset($index['markup_percent']) ? trim($row[$index['markup_percent']] ?? '') : '';
            $taxRate = isset($index['tax_rate']) ? trim($row[$index['tax_rate']] ?? '') : '';
            $isTaxInclusive = isset($index['is_tax_inclusive']) ? trim($row[$index['is_tax_inclusive']] ?? '1') : '1';

            $stockQuantity = isset($index['stock_quantity']) ? trim($row[$index['stock_quantity']] ?? '') : '';
            $reorderPoint = isset($index['reorder_point']) ? trim($row[$index['reorder_point']] ?? '') : '';
            $preferredQuantity = isset($index['preferred_quantity']) ? trim($row[$index['preferred_quantity']] ?? '') : '';
            $warningQuantity = isset($index['warning_quantity']) ? trim($row[$index['warning_quantity']] ?? '') : '';

            $measurementUnit = isset($index['measurement_unit']) ? trim($row[$index['measurement_unit']] ?? '') : '';
            $supplierName = isset($index['supplier_name']) ? trim($row[$index['supplier_name']] ?? '') : '';
            $isService = isset($index['is_service']) ? trim($row[$index['is_service']] ?? '0') : '0';

            \Log::info('IMPORT DEBUG row', [
                'raw' => $row,
                'name' => $name,
                'category' => $categoryName,
            ]);

            if ($name === '' || $categoryName === '') {
                continue; // datos mínimos faltantes
            }

            $category = \App\Models\Category::firstOrCreate(['name' => $categoryName], [
                'is_active' => true,
            ]);

            // Normalizar is_active a booleano
            if ($isActiveRaw === '') {
                $isActiveBool = true; // por defecto activo si viene vacío
            } else {
                $normalized = mb_strtolower($isActiveRaw, 'UTF-8');
                // Quitar acentos simples para comparar (sí -> si)
                $normalized = strtr($normalized, [
                    'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
                    'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
                ]);

                $trueValues = ['1', 'true', 'verdadero', 'yes', 'y', 'si', 's'];
                $falseValues = ['0', 'false', 'falso', 'no', 'n'];

                if (in_array($normalized, $trueValues, true)) {
                    $isActiveBool = true;
                } elseif (in_array($normalized, $falseValues, true)) {
                    $isActiveBool = false;
                } else {
                    // Fallback: interpretar numéricamente como antes
                    $isActiveBool = ((int) $isActiveRaw === 1);
                }
            }

            $attrs = [
                'category_id' => $category->id,
                'name' => $name,
                'sku' => $sku !== '' ? $sku : null,
                'description' => $description !== '' ? $description : null,
                'price' => $price !== '' ? (float) $price : 0.0,
                'is_active' => $isActiveBool,
                'image_path' => $imagePath !== '' ? $imagePath : null,
                'cost' => $cost !== '' ? (float) $cost : null,
                'markup_percent' => $markupPercent !== '' ? (float) $markupPercent : null,
                'tax_rate' => $taxRate !== '' ? (float) $taxRate : 0.0,
                'is_tax_inclusive' => (int) $isTaxInclusive === 1,
                'stock_quantity' => $stockQuantity !== '' ? (float) $stockQuantity : 0.0,
                'reorder_point' => $reorderPoint !== '' ? (float) $reorderPoint : 0.0,
                'preferred_quantity' => $preferredQuantity !== '' ? (float) $preferredQuantity : 0.0,
                'warning_quantity' => $warningQuantity !== '' ? (float) $warningQuantity : 0.0,
                'measurement_unit' => $measurementUnit !== '' ? $measurementUnit : null,
                'supplier_name' => $supplierName !== '' ? $supplierName : null,
                'is_service' => (int) $isService === 1,
            ];

            // Buscar producto existente por SKU si existe; si no, por combinación categoría + nombre.
            $product = null;
            if ($sku !== '') {
                $product = Product::where('sku', $sku)->first();
            }
            if (!$product) {
                $product = Product::where('category_id', $category->id)
                    ->where('name', $name)
                    ->first();
            }

            if ($product) {
                // Comprobar si todos los campos relevantes ya son iguales; si sí, saltar (evitar duplicados reales).
                $allEqual = true;
                foreach ($attrs as $key => $value) {
                    $current = $product->{$key};

                    // Normalizar booleanos
                    if (is_bool($current) || is_bool($value)) {
                        $current = (bool) $current;
                        $value = (bool) $value;
                    }

                    // Normalizar numéricos para comparación
                    if (is_numeric($current) && is_numeric($value)) {
                        if ((float) $current !== (float) $value) {
                            $allEqual = false;
                            break;
                        }
                    } else {
                        if ((string) ($current ?? '') !== (string) ($value ?? '')) {
                            $allEqual = false;
                            break;
                        }
                    }
                }

                if ($allEqual) {
                    // Fila totalmente duplicada, no hacer nada.
                    continue;
                }

                $product->update($attrs);
                $updated++;
            } else {
                $product = Product::create($attrs);
                $created++;
            }

            // Si tenemos un SKU numérico, aseguramos un barcode asociado para el TPV.
            // La tabla product_barcodes tiene un índice UNIQUE solo sobre "barcode",
            // por lo que debemos buscar por barcode primero para evitar violar la restricción.
            if ($sku !== '' && ctype_digit($sku)) {
                $existingBarcode = ProductBarcode::where('barcode', $sku)->first();

                if (!$existingBarcode) {
                    // No existe ningún registro con este barcode: lo creamos para este producto.
                    ProductBarcode::create([
                        'product_id' => $product->id,
                        'barcode' => $sku,
                        'label' => null,
                        'multiplier' => 1,
                    ]);
                } elseif ($existingBarcode->product_id !== $product->id) {
                    // El barcode ya existe pero apunta a otro producto.
                    // Lo reasignamos al producto actual para mantener la unicidad global.
                    $existingBarcode->update([
                        'product_id' => $product->id,
                    ]);
                }
                // Si ya existe y pertenece a este mismo producto, no hacemos nada.
            }
        }

        fclose($handle);

        return redirect()->route('products.index')
            ->with('status', "IMPORT TEST XYZ - Importación completada: {$created} creados, {$updated} actualizados.");
    }
}
