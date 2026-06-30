<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\AuditLog;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Models\Location;
use App\Services\TranslationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $selectedCategoryId = $request->query('category_id');
        $search = $request->query('search');

        $query = Product::with(['category', 'barcodes'])->orderBy('name');

        if ($selectedCategoryId) {
            $query->where('category_id', $selectedCategoryId);
        }

        if ($search) {
            $searchTerm = trim($search);
            if ($searchTerm !== '') {
                $query->where(function ($q) use ($searchTerm) {
                    $like = '%' . $searchTerm . '%';
                    $q->where('name', 'like', $like)
                        ->orWhere('sku', 'like', $like)
                        ->orWhereHas('barcodes', function ($qb) use ($like) {
                            $qb->where('barcode', 'like', $like);
                        });
                });
            }
        }

        $products = $query->get();
        $categories = Category::orderBy('name')->get();

        return view('products.index', compact('products', 'categories', 'selectedCategoryId', 'search'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        $units = Unit::where('is_active', true)->orderBy('category')->orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        $locations = Location::orderBy('name')->get();
        return view('products.create', compact('categories', 'units', 'warehouses', 'locations'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:50|unique:products,sku',
            'description' => 'nullable|string',
            'description_zh' => 'nullable|string',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'location_id' => 'nullable|exists:locations,id',
            'aisle' => 'nullable|string|max:50',
            'shelf' => 'nullable|string|max:50',
            'rack' => 'nullable|string|max:50',
            'bin' => 'nullable|string|max:50',
            'section' => 'nullable|string|max:50',
            'price' => 'required|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'markup_percent' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
            'default_unit' => 'nullable|string|max:20',
            'stock_unit_id' => 'nullable|exists:units,id',
            'base_unit_id' => 'nullable|exists:units,id',
            'barcodes' => 'nullable|array',
            // Barcode opcional: cualquier cadena corta, distinta y única en la tabla de barcodes
            'barcodes.*.barcode' => 'nullable|string|max:50|distinct|unique:product_barcodes,barcode',
            'barcodes.*.label' => 'nullable|string|max:50',
            'barcodes.*.multiplier' => 'nullable|numeric|min:0.001',
        ]);

        $data['is_active'] = $request->boolean('is_active', false);
        $data['is_stock_tracked'] = $request->boolean('is_stock_tracked', false);
        $data['is_prepared'] = $request->boolean('is_prepared', false);
        $data['is_raw_material'] = $request->boolean('is_raw_material', false);
        $data['is_service'] = $request->boolean('is_service', false);
        $data['is_tax_inclusive'] = $request->boolean('is_tax_inclusive', true);
        $data['is_price_change_allowed'] = $request->boolean('is_price_change_allowed', false);

        if (!empty($data['sku'])) {
            $data['sku'] = trim($data['sku']);
        }

        $data = $this->syncLocationDetails($data);

        if (empty($data['description_zh']) && !empty($data['description'])) {
            $data['description_zh'] = app(TranslationService::class)->translateToChinese($data['description']);
        }

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $data['image_path'] = $path;
        }

        // Si viene costo y margen pero no precio coherente, recalcular precio
        if (isset($data['cost']) && isset($data['markup_percent']) && $data['cost'] !== null && $data['markup_percent'] !== null) {
            $cost = (float) $data['cost'];
            $markup = (float) $data['markup_percent'];
            if ($cost >= 0 && $markup >= 0) {
                $data['price'] = round($cost * (1 + $markup / 100), 2);
            }
        } elseif (isset($data['cost']) && $data['cost'] !== null && $data['cost'] > 0 && isset($data['price'])) {
            // Si vienen costo y precio pero no margen, calcular margen
            if (!isset($data['markup_percent']) || $data['markup_percent'] === null) {
                $data['markup_percent'] = round(((float) $data['price'] / (float) $data['cost'] - 1) * 100, 2);
            }
        }

        $barcodes = $data['barcodes'] ?? [];
        unset($data['barcodes']);

        $uploadedImages = $request->file('images') ?? [];
        $legacyImage = $request->file('image');

        DB::transaction(function () use ($data, $barcodes, $uploadedImages, $legacyImage) {
            $product = Product::create($data);

            $rows = [];
            foreach ($barcodes as $row) {
                $barcode = isset($row['barcode']) ? trim((string) $row['barcode']) : '';
                if ($barcode === '') {
                    continue;
                }
                $rows[] = [
                    'barcode' => $barcode,
                    'label' => isset($row['label']) && trim((string) $row['label']) !== '' ? trim((string) $row['label']) : null,
                    'multiplier' => isset($row['multiplier']) && (string) $row['multiplier'] !== '' ? (float) $row['multiplier'] : 1,
                ];
            }

            if (count($rows) > 0) {
                $product->barcodes()->createMany($rows);
            }

            $this->storeProductImages($product, $uploadedImages, $legacyImage);
        });

        return redirect()->route('products.index');
    }

    public function edit(Product $product)
    {
        $categories = Category::orderBy('name')->get();
        $units = Unit::where('is_active', true)->orderBy('category')->orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        $locations = Location::orderBy('name')->get();
        $product->load('barcodes');
        return view('products.edit', compact('product', 'categories', 'units', 'warehouses', 'locations'));
    }

    public function label(Product $product)
    {
        return view('products.label', compact('product'));
    }

    public function bulkLabels(Request $request)
    {
        $warehouseId = $request->query('warehouse_id');
        $locationId = $request->query('location_id');
        $categoryId = $request->query('category_id');
        $search = $request->query('search');

        $query = Product::with(['category', 'barcodes', 'warehouse', 'location'])
            ->where('is_active', true)
            ->orderBy('name');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($search) {
            $searchTerm = trim($search);
            if ($searchTerm !== '') {
                $query->where(function ($q) use ($searchTerm) {
                    $like = '%' . $searchTerm . '%';
                    $q->where('name', 'like', $like)
                        ->orWhere('sku', 'like', $like)
                        ->orWhereHas('barcodes', function ($qb) use ($like) {
                            $qb->where('barcode', 'like', $like);
                        });
                });
            }
        }

        $products = $query->get();
        $warehouses = Warehouse::orderBy('name')->get();
        $locations = Location::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('products.labels', compact(
            'products',
            'warehouses',
            'locations',
            'categories',
            'warehouseId',
            'locationId',
            'categoryId',
            'search'
        ));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:50|unique:products,sku,' . $product->id,
            'description' => 'nullable|string',
            'description_zh' => 'nullable|string',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'location_id' => 'nullable|exists:locations,id',
            'aisle' => 'nullable|string|max:50',
            'shelf' => 'nullable|string|max:50',
            'rack' => 'nullable|string|max:50',
            'bin' => 'nullable|string|max:50',
            'section' => 'nullable|string|max:50',
            'price' => 'required|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'markup_percent' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
            'main_image_id' => 'nullable|exists:product_images,id',
            'delete_images' => 'nullable|array',
            'delete_images.*' => 'exists:product_images,id',
            'default_unit' => 'nullable|string|max:20',
            'stock_unit_id' => 'nullable|exists:units,id',
            'base_unit_id' => 'nullable|exists:units,id',
            'barcodes' => 'nullable|array',
            // En edición: mismo criterio flexible, evitando colisión con otros productos
            'barcodes.*.barcode' => 'nullable|string|max:50|distinct|unique:product_barcodes,barcode,' . $product->id . ',product_id',
            'barcodes.*.label' => 'nullable|string|max:50',
            'barcodes.*.multiplier' => 'nullable|numeric|min:0.001',
        ]);

        $data['is_active'] = $request->boolean('is_active', false);
        $data['is_stock_tracked'] = $request->boolean('is_stock_tracked', false);
        $data['is_prepared'] = $request->boolean('is_prepared', false);
        $data['is_raw_material'] = $request->boolean('is_raw_material', false);
        $data['is_service'] = $request->boolean('is_service', false);
        $data['is_tax_inclusive'] = $request->boolean('is_tax_inclusive', true);
        $data['is_price_change_allowed'] = $request->boolean('is_price_change_allowed', false);

        if (!empty($data['sku'])) {
            $data['sku'] = trim($data['sku']);
        }

        $data = $this->syncLocationDetails($data);

        if (empty($data['description_zh']) && !empty($data['description'])) {
            $data['description_zh'] = app(TranslationService::class)->translateToChinese($data['description']);
        }

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $data['image_path'] = $path;
        }

        // Recalcular precio o margen según costo / margen
        if (isset($data['cost']) && isset($data['markup_percent']) && $data['cost'] !== null && $data['markup_percent'] !== null) {
            $cost = (float) $data['cost'];
            $markup = (float) $data['markup_percent'];
            if ($cost >= 0 && $markup >= 0) {
                $data['price'] = round($cost * (1 + $markup / 100), 2);
            }
        } elseif (isset($data['cost']) && $data['cost'] !== null && $data['cost'] > 0 && isset($data['price'])) {
            if (!isset($data['markup_percent']) || $data['markup_percent'] === null) {
                $data['markup_percent'] = round(((float) $data['price'] / (float) $data['cost'] - 1) * 100, 2);
            }
        }

        $barcodes = $data['barcodes'] ?? [];
        unset($data['barcodes']);

        $uploadedImages = $request->file('images') ?? [];
        $legacyImage = $request->file('image');
        $mainImageId = $request->input('main_image_id');
        $deleteImages = $request->input('delete_images', []);

        $before = $product->only([
            'category_id',
            'name',
            'sku',
            'description',
            'price',
            'is_active',
            'image_path',
            'is_stock_tracked',
            'is_prepared',
            'is_raw_material',
            'default_unit',
        ]);

        DB::transaction(function () use ($product, $data, $barcodes, $uploadedImages, $legacyImage, $mainImageId, $deleteImages) {
            $product->update($data);

            $rows = [];
            foreach ($barcodes as $row) {
                $barcode = isset($row['barcode']) ? trim((string) $row['barcode']) : '';
                if ($barcode === '') {
                    continue;
                }
                $rows[] = [
                    'barcode' => $barcode,
                    'label' => isset($row['label']) && trim((string) $row['label']) !== '' ? trim((string) $row['label']) : null,
                    'multiplier' => isset($row['multiplier']) && (string) $row['multiplier'] !== '' ? (float) $row['multiplier'] : 1,
                ];
            }

            $product->barcodes()->delete();
            if (count($rows) > 0) {
                $product->barcodes()->createMany($rows);
            }

            foreach ($deleteImages as $imageId) {
                $image = $product->images()->find($imageId);
                if ($image) {
                    \Storage::disk('public')->delete($image->path);
                    $image->delete();
                }
            }

            $this->storeProductImages($product, $uploadedImages, $legacyImage);
            $this->setMainImage($product, $mainImageId);
        });

        $after = $product->only([
            'category_id',
            'name',
            'sku',
            'description',
            'price',
            'is_active',
            'image_path',
            'is_stock_tracked',
            'is_prepared',
            'is_raw_material',
            'default_unit',
        ]);

        // Auditoría de cambios de producto (precio, estado, etc.)
        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'product_updated',
            'entity_type' => 'product',
            'entity_id' => $product->id,
            'description' => sprintf('Producto %s (#%d) actualizado.', $product->name, $product->id),
            'data_before' => $before,
            'data_after' => $after,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->header('User-Agent'),
        ]);

        return redirect()->route('products.index');
    }

    private function storeProductImages(Product $product, array $uploadedImages, $legacyImage): void
    {
        $images = [];

        if ($legacyImage) {
            $images[] = $legacyImage;
        }

        foreach ($uploadedImages as $file) {
            $images[] = $file;
        }

        foreach ($images as $index => $file) {
            $path = $file->store('products', 'public');
            $product->images()->create([
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'is_main' => $index === 0 && !$product->images()->where('is_main', true)->exists(),
                'sort_order' => $product->images()->count() + $index,
            ]);
        }
    }

    private function setMainImage(Product $product, ?int $mainImageId): void
    {
        if (!$mainImageId) {
            return;
        }

        $product->images()->update(['is_main' => false]);
        $product->images()->where('id', $mainImageId)->update(['is_main' => true]);
    }

    private function syncLocationDetails(array $data): array
    {
        if (!empty($data['location_id'])) {
            $location = Location::find($data['location_id']);
            if ($location) {
                $data['warehouse_id'] = $location->warehouse_id;
                $data['aisle'] = $location->aisle;
                $data['shelf'] = $location->shelf;
                $data['rack'] = $location->rack;
                $data['bin'] = $location->bin;
                $data['section'] = $location->section;
            }
        } else {
            $data['warehouse_id'] = null;
            $data['aisle'] = null;
            $data['shelf'] = null;
            $data['rack'] = null;
            $data['bin'] = null;
            $data['section'] = null;
        }

        return $data;
    }

    public function destroy(Product $product)
    {
        try {
            $product->delete();

            return redirect()
                ->route('products.index')
                ->with('success', 'Producto eliminado correctamente.');
        } catch (QueryException $exception) {
            report($exception);

            return redirect()
                ->route('products.index')
                ->with('error', 'No se pudo eliminar el producto porque existen registros asociados (recetas, inventario o compras). Puede desactivarlo en lugar de eliminarlo.');
        }
    }
}
