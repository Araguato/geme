<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Unifica el stock: hasta ahora las variantes y la importación CSV guardaban la existencia
 * solo en products.stock_quantity, que las ventas nunca descontaban. Se pasa ese stock a
 * stock_items (depósito principal) con su movimiento en el kardex, y products.stock_quantity
 * queda como suma de las existencias por ubicación.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $pending = DB::table('products')
            ->where('stock_quantity', '>', 0)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('stock_items')
                    ->whereColumn('stock_items.product_id', 'products.id');
            })
            ->get(['id', 'stock_quantity', 'stock_unit_id', 'default_unit', 'cost']);

        if ($pending->isNotEmpty()) {
            $warehouseId = DB::table('warehouses')->where('code', 'MAIN')->value('id')
                ?? DB::table('warehouses')->where('is_active', true)->orderBy('sort_order')->orderBy('id')->value('id');
            if (!$warehouseId) {
                $warehouseId = DB::table('warehouses')->insertGetId([
                    'code' => 'MAIN', 'name' => 'Almacén principal', 'is_active' => true,
                    'sort_order' => 0, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }

            $locationId = DB::table('locations')->where('warehouse_id', $warehouseId)->where('is_active', true)
                ->orderBy('sort_order')->orderBy('id')->value('id');
            if (!$locationId) {
                $locationId = DB::table('locations')->insertGetId([
                    'warehouse_id' => $warehouseId, 'code' => 'GEN', 'name' => 'General', 'is_active' => true,
                    'sort_order' => 0, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }

            foreach ($pending as $product) {
                $qty = (float) $product->stock_quantity;
                $cost = (float) ($product->cost ?? 0);

                DB::table('stock_items')->insert([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouseId,
                    'location_id' => $locationId,
                    'unit_id' => $product->stock_unit_id,
                    'quantity' => $qty,
                    'unit' => $product->default_unit ?: null,
                    'average_cost' => $cost,
                    'min_quantity' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('stock_movements')->insert([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouseId,
                    'location_id' => $locationId,
                    'unit_id' => $product->stock_unit_id,
                    'movement_date' => $now,
                    'type' => 'adjustment',
                    'reason' => 'initial_stock',
                    'quantity' => $qty,
                    'unit' => $product->default_unit ?: null,
                    'unit_cost' => $cost,
                    'total_cost' => $qty * $cost,
                    'running_quantity' => $qty,
                    'running_average_cost' => $cost,
                    'reference_type' => 'migration',
                    'reference_id' => null,
                    'performed_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // products.stock_quantity = suma de existencias por ubicación
        DB::table('products')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('stock_items')
                    ->whereColumn('stock_items.product_id', 'products.id');
            })
            ->update([
                'stock_quantity' => DB::raw('(SELECT COALESCE(SUM(quantity), 0) FROM stock_items WHERE stock_items.product_id = products.id)'),
            ]);
    }

    public function down(): void
    {
        // Migración de datos: no se revierte (las existencias siguen en stock_items).
    }
};
