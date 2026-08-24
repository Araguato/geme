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

        $query = Product::with(['category', 'barcodes'])
            ->whereNull('parent_product_id')
            ->orderBy('name');

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

    public function search(Request $request)
    {
        $search = $request->query('search');

        $query = Product::with(['category', 'barcodes', 'mainImage', 'images', 'parent'])
            ->where('is_active', true)
            ->orderBy('name');

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

        $products = $query->limit(50)->get();

        return view('products.search', compact('products', 'search'));
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
            'variant_attributes' => 'nullable|array',
            'variant_attributes.*.name' => 'required_with:has_variants|string|max:100',
            'variant_attributes.*.values' => 'required_with:has_variants|string',
            'variants' => 'nullable|array',
            'variants.*.sku' => 'nullable|string|max:50|unique:products,sku',
            'variants.*.price' => 'required_with:has_variants|numeric|min:0',
            'variants.*.stock_quantity' => 'nullable|numeric|min:0',
            'variants.*.variant_attributes' => 'required_with:has_variants|string',
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
                $markup = round(((float) $data['price'] / (float) $data['cost'] - 1) * 100, 2);
                $data['markup_percent'] = max(0, $markup);
            }
        }

        $barcodes = $data['barcodes'] ?? [];
        unset($data['barcodes']);

        $uploadedImages = $request->file('images') ?? [];
        $legacyImage = $request->file('image');

        $hasVariants = $request->boolean('has_variants', false);
        $variantAttributes = $request->input('variant_attributes', []);
        $variants = $request->input('variants', []);

        DB::transaction(function () use ($data, $barcodes, $uploadedImages, $legacyImage, $hasVariants, $variantAttributes, $variants) {
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

            if ($hasVariants) {
                foreach ($variantAttributes as $attr) {
                    $name = trim($attr['name'] ?? '');
                    $values = array_values(array_unique(array_filter(array_map('trim', explode(',', $attr['values'] ?? '')))));
                    if ($name && count($values)) {
                        $product->variantAttributes()->create([
                            'attribute_name' => $name,
                            'values' => $values,
                        ]);
                    }
                }

                foreach ($variants as $variant) {
                    $sku = isset($variant['sku']) ? trim($variant['sku']) : null;
                    if ($sku === '') {
                        $sku = null;
                    }
                    $variantData = [
                        'parent_product_id' => $product->id,
                        'category_id' => $product->category_id,
                        'name' => $product->name,
                        'sku' => $sku,
                        'description' => $product->description,
                        'description_zh' => $product->description_zh,
                        'price' => $variant['price'],
                        'cost' => $product->cost,
                        'markup_percent' => $product->markup_percent,
                        'is_active' => $product->is_active,
                        'is_stock_tracked' => $product->is_stock_tracked,
                        'is_service' => $product->is_service,
                        'is_tax_inclusive' => $product->is_tax_inclusive,
                        'is_price_change_allowed' => $product->is_price_change_allowed,
                        'stock_quantity' => $variant['stock_quantity'] ?? 0,
                        'image_path' => $product->image_path,
                        'variant_attributes' => json_decode($variant['variant_attributes'], true),
                    ];
                    Product::create($variantData);
                }
            }
        });

        return redirect()->route('products.index');
    }

    public function edit(Product $product)
    {
        $categories = Category::orderBy('name')->get();
        $units = Unit::where('is_active', true)->orderBy('category')->orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        $locations = Location::orderBy('name')->get();
        $product->load(['barcodes', 'variants', 'variantAttributes']);
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
            'variant_attributes' => 'nullable|array',
            'variant_attributes.*.name' => 'nullable|string|max:100',
            'variant_attributes.*.values' => 'nullable|string',
            'update_variants' => 'nullable|array',
            'update_variants.*.sku' => 'nullable|string|max:50',
            'update_variants.*.price' => 'nullable|numeric|min:0',
            'update_variants.*.stock_quantity' => 'nullable|numeric|min:0',
            'new_variants' => 'nullable|array',
            'new_variants.*.sku' => 'nullable|string|max:50|unique:products,sku',
            'new_variants.*.price' => 'required_with:new_variants|numeric|min:0',
            'new_variants.*.stock_quantity' => 'nullable|numeric|min:0',
            'new_variants.*.variant_attributes' => 'required_with:new_variants|string',
            'delete_variants' => 'nullable|array',
            'delete_variants.*' => 'exists:products,id',
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
                $markup = round(((float) $data['price'] / (float) $data['cost'] - 1) * 100, 2);
                $data['markup_percent'] = max(0, $markup);
            }
        }

        $barcodes = $data['barcodes'] ?? [];
        unset($data['barcodes']);

        // Limpiar campos de variantes de $data para evitar guardarlos como JSON en el producto padre
        unset($data['variant_attributes']);
        unset($data['update_variants']);
        unset($data['new_variants']);
        unset($data['delete_variants']);

        $uploadedImages = $request->file('images') ?? [];
        $legacyImage = $request->file('image');
        $mainImageId = $request->input('main_image_id');
        $deleteImages = $request->input('delete_images', []);

        $variantAttributes = $request->input('variant_attributes', []);
        $updateVariants = $request->input('update_variants', []);
        $newVariants = $request->input('new_variants', []);
        $deleteVariants = $request->input('delete_variants', []);

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

        DB::transaction(function () use ($product, $data, $barcodes, $uploadedImages, $legacyImage, $mainImageId, $deleteImages, $variantAttributes, $updateVariants, $newVariants, $deleteVariants) {
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

            // --- VARIANTES ---
            if ($product->isParent() || count($variantAttributes) > 0) {
                // Sync atributos: borrar todo y recrear
                $product->variantAttributes()->delete();
                foreach ($variantAttributes as $attr) {
                    $name = trim($attr['name'] ?? '');
                    $values = array_values(array_unique(array_filter(array_map('trim', explode(',', $attr['values'] ?? '')))));
                    if ($name && count($values)) {
                        $product->variantAttributes()->create([
                            'attribute_name' => $name,
                            'values' => $values,
                        ]);
                    }
                }

                // Actualizar variantes existentes
                foreach ($updateVariants as $variantId => $variantData) {
                    $variant = $product->variants()->find($variantId);
                    if ($variant) {
                        $update = [];
                        if (array_key_exists('sku', $variantData)) {
                            $sku = trim($variantData['sku']);
                            $update['sku'] = $sku === '' ? null : $sku;
                        }
                        if (isset($variantData['price'])) {
                            $update['price'] = (float) $variantData['price'];
                        }
                        if (isset($variantData['stock_quantity'])) {
                            $update['stock_quantity'] = (float) $variantData['stock_quantity'];
                        }
                        if (count($update) > 0) {
                            $variant->update($update);
                        }
                    }
                }

                // Crear nuevas variantes
                foreach ($newVariants as $variantData) {
                    $sku = isset($variantData['sku']) ? trim($variantData['sku']) : null;
                    if ($sku === '') {
                        $sku = null;
                    }
                    Product::create([
                        'parent_product_id' => $product->id,
                        'category_id' => $product->category_id,
                        'name' => $product->name,
                        'sku' => $sku,
                        'description' => $product->description,
                        'description_zh' => $product->description_zh,
                        'price' => $variantData['price'],
                        'cost' => $product->cost,
                        'markup_percent' => $product->markup_percent,
                        'is_active' => $product->is_active,
                        'is_stock_tracked' => $product->is_stock_tracked,
                        'is_service' => $product->is_service,
                        'is_tax_inclusive' => $product->is_tax_inclusive,
                        'is_price_change_allowed' => $product->is_price_change_allowed,
                        'stock_quantity' => $variantData['stock_quantity'] ?? 0,
                        'image_path' => $product->image_path,
                        'variant_attributes' => json_decode($variantData['variant_attributes'], true),
                    ]);
                }

                // Eliminar variantes marcadas
                if (count($deleteVariants) > 0) {
                    $product->variants()->whereIn('id', $deleteVariants)->delete();
                }
            }
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

    public function bulkPriceEdit(Request $request)
    {
        $categoryId = $request->query('category_id');
        $categories = Category::orderBy('name')->get();

        $query = Product::where('is_active', true);
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $stats = $query->clone()
            ->selectRaw('COUNT(*) as count, MIN(price) as min_price, MAX(price) as max_price, AVG(price) as avg_price')
            ->first();

        $products = $query->orderBy('name')->limit(50)->get(['id', 'name', 'sku', 'price']);

        return view('products.prices.edit', compact('categories', 'categoryId', 'stats', 'products'));
    }

    public function bulkPriceUpdate(Request $request)
    {
        $data = $request->validate([
            'percentage' => 'required|numeric',
            'category_id' => 'nullable|exists:categories,id',
            'round_to' => 'nullable|in:0.01,0.05,0.10,0.25,0.50,1.00',
        ]);

        $percentage = (float) $data['percentage'];
        $factor = 1 + ($percentage / 100);
        $roundTo = isset($data['round_to']) ? (float) $data['round_to'] : null;

        $query = Product::where('is_active', true);
        if (!empty($data['category_id'])) {
            $query->where('category_id', $data['category_id']);
        }

        $count = 0;
        $query->chunkById(100, function ($products) use ($factor, $roundTo, &$count) {
            foreach ($products as $product) {
                $newPrice = $product->price * $factor;
                if ($roundTo !== null && $roundTo > 0) {
                    $newPrice = round($newPrice / $roundTo) * $roundTo;
                }
                $newPrice = max(0, round($newPrice, 2));
                $product->update(['price' => $newPrice]);
                $count++;
            }
        });

        return redirect()
            ->route('products.prices.edit')
            ->with('success', "Precios actualizados: {$count} productos afectados con un ajuste del {$percentage}%.");
    }
}
