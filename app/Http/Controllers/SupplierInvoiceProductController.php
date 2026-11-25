<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Apoyo del formulario de facturas de proveedor: buscar productos al escribir y crear
 * uno nuevo en el momento si todavía no existe.
 */
class SupplierInvoiceProductController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        $query = Product::query()
            ->where('is_active', true)
            // Un producto con variantes se compra por variante, no por el padre
            ->where(function ($q) {
                $q->whereNotNull('parent_product_id')->orWhereDoesntHave('variants');
            })
            ->orderBy('name');

        if ($term !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term) . '%';
            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('sku', 'like', $like)
                    ->orWhereHas('barcodes', fn ($b) => $b->where('barcode', 'like', $like));
            });
        }

        return response()->json([
            'products' => $query->limit(20)->get()->map(fn (Product $p) => $this->payload($p))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'sku' => 'nullable|string|max:50|unique:products,sku',
            'default_unit' => 'nullable|string|max:20',
            'cost' => 'nullable|numeric|min:0',
            'price' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'is_stock_tracked' => 'nullable|boolean',
        ], [
            'name.required' => 'Escribe el nombre del producto.',
            'category_id.required' => 'Elige una categoría.',
            'sku.unique' => 'Ya existe un producto con ese SKU.',
        ]);

        $name = trim($data['name']);

        $existing = Product::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
        if ($existing) {
            return response()->json([
                'message' => 'Ya existe un producto con ese nombre.',
                'errors' => ['name' => ["Ya existe «{$existing->name}»: búscalo en la lista en lugar de crearlo otra vez."]],
                'existing' => $this->payload($existing),
            ], 422);
        }

        $cost = (float) ($data['cost'] ?? 0);

        $product = Product::create([
            'name' => $name,
            'category_id' => $data['category_id'],
            'sku' => isset($data['sku']) && trim($data['sku']) !== '' ? trim($data['sku']) : null,
            'default_unit' => $data['default_unit'] ?? null,
            'cost' => $cost,
            // Sin precio de venta indicado se usa el costo: debe revisarse en Productos
            'price' => isset($data['price']) ? (float) $data['price'] : $cost,
            'tax_rate' => (float) ($data['tax_rate'] ?? 0),
            'is_active' => true,
            // Por defecto controla inventario, para que la compra sume stock
            'is_stock_tracked' => $request->boolean('is_stock_tracked', true),
            'is_prepared' => false,
            'is_raw_material' => false,
            'is_service' => false,
            'is_tax_inclusive' => true,
            'is_price_change_allowed' => false,
        ]);

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'product_created_from_supplier_invoice',
            'entity_type' => 'Product',
            'entity_id' => $product->id,
            'description' => "Creó el producto «{$product->name}» desde una factura de proveedor",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['product' => $this->payload($product), 'price_from_cost' => !isset($data['price'])], 201);
    }

    private function payload(Product $p): array
    {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'default_unit' => $p->default_unit,
            'cost' => $p->cost !== null ? (float) $p->cost : null,
            'tax_rate' => $p->tax_rate !== null ? (float) $p->tax_rate : null,
            'is_stock_tracked' => (bool) $p->is_stock_tracked,
        ];
    }
}
