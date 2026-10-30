<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Los seeders creaban dos depósitos "Almacén principal" (códigos MAIN y PRINCIPAL), y las
 * compras y recetas podían dejar stock sin depósito. Se une todo en MAIN sin cambiar las
 * cantidades: PRINCIPAL pasa a MAIN con sus ubicaciones, y su "General" se une a la de MAIN.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $now = now();
            $mainId = DB::table('warehouses')->where('code', 'MAIN')->value('id');
            $principalId = DB::table('warehouses')->where('code', 'PRINCIPAL')->value('id');

            if ($principalId && !$mainId) {
                DB::table('warehouses')->where('id', $principalId)->update(['code' => 'MAIN', 'updated_at' => $now]);
                $mainId = $principalId;
                $principalId = null;
            }

            if ($principalId) {
                $this->moveWarehouse($principalId, $mainId);
            }

            if (DB::table('stock_items')->whereNull('warehouse_id')->exists()) {
                $mainId = $mainId ?? DB::table('warehouses')->insertGetId([
                    'code' => 'MAIN', 'name' => 'Almacén principal', 'is_active' => true,
                    'sort_order' => 0, 'created_at' => $now, 'updated_at' => $now,
                ]);
                $locationId = DB::table('locations')->where('warehouse_id', $mainId)->where('is_active', true)
                    ->orderBy('sort_order')->orderBy('id')->value('id')
                    ?? DB::table('locations')->insertGetId([
                        'warehouse_id' => $mainId, 'code' => 'GEN', 'name' => 'General', 'is_active' => true,
                        'sort_order' => 0, 'created_at' => $now, 'updated_at' => $now,
                    ]);

                DB::table('stock_items')->whereNull('warehouse_id')->update([
                    'warehouse_id' => $mainId,
                    'location_id' => $locationId,
                ]);
            }

            if ($mainId) {
                $this->mergeDuplicateRows($mainId);
            }
        });
    }

    private function moveWarehouse(int $fromId, int $toId): void
    {
        foreach (DB::table('locations')->where('warehouse_id', $fromId)->get() as $location) {
            $code = $location->code === 'GENERAL' ? 'GEN' : $location->code;
            $targetId = DB::table('locations')->where('warehouse_id', $toId)->where('code', $code)->value('id');

            if ($targetId) {
                foreach (['stock_items', 'stock_movements', 'products', 'order_items'] as $table) {
                    DB::table($table)->where('location_id', $location->id)->update(['location_id' => $targetId]);
                }
                DB::table('locations')->where('id', $location->id)->delete();
            } else {
                DB::table('locations')->where('id', $location->id)->update(['warehouse_id' => $toId, 'code' => $code]);
            }
        }

        foreach (['stock_items', 'stock_movements', 'products', 'order_items', 'sales_locations'] as $table) {
            DB::table($table)->where('warehouse_id', $fromId)->update(['warehouse_id' => $toId]);
        }
        DB::table('stock_movements')->where('to_warehouse_id', $fromId)->update(['to_warehouse_id' => $toId]);
        DB::table('warehouses')->where('id', $fromId)->delete();
    }

    private function mergeDuplicateRows(int $warehouseId): void
    {
        $rows = DB::table('stock_items')->where('warehouse_id', $warehouseId)->orderBy('id')->get();

        foreach ($rows->groupBy(fn ($row) => $row->product_id . '-' . $row->location_id) as $group) {
            if ($group->count() < 2) {
                continue;
            }

            $quantity = $group->sum(fn ($row) => (float) $row->quantity);
            $value = $group->sum(fn ($row) => (float) $row->quantity * (float) $row->average_cost);

            DB::table('stock_items')->where('id', $group->first()->id)->update([
                'quantity' => $quantity,
                'average_cost' => $quantity > 0 ? $value / $quantity : (float) $group->first()->average_cost,
                'min_quantity' => $group->max(fn ($row) => (float) $row->min_quantity),
            ]);
            DB::table('stock_items')->whereIn('id', $group->skip(1)->pluck('id'))->delete();
        }
    }

    public function down(): void
    {
        // Migración de datos: no se revierte.
    }
};
