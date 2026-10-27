<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\SupplierInvoice;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Descuenta inventario para un pedido ya pagado/finalizado.
     * Es idempotente: si ya existen movimientos para este pedido, no vuelve a descontar.
     */
    public static function consumeForOrder(Order $order, ?int $userId = null): void
    {
        // Evitar doble procesamiento
        $alreadyProcessed = StockMovement::where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->exists();

        if ($alreadyProcessed) {
            return;
        }

        $order->loadMissing('items.product');

        DB::transaction(function () use ($order, $userId) {
            foreach ($order->items as $item) {
                /** @var OrderItem $item */
                $product = $item->product;
                if (!$product || !$product->is_stock_tracked) {
                    continue;
                }

                if ($product->is_prepared) {
                    self::consumePreparedProduct($product, $item->quantity, $userId, $order);
                } else {
                    self::applyStockChange(
                        product: $product,
                        quantity: -1 * (float) $item->quantity,
                        reason: 'sale',
                        type: 'out',
                        userId: $userId,
                        referenceType: 'order',
                        referenceId: $order->id,
                    );
                }
            }
        });
    }

    /**
     * Descuenta del inventario una línea vendida en el TPV.
     * Usa el depósito/ubicación elegidos; si no vienen (o no coinciden), toma la
     * ubicación con más existencias del producto. Con $restrictWarehouseId (depósito
     * del negocio donde está abierta la caja) solo descuenta de ese depósito.
     * Registra el movimiento en el kardex.
     */
    public static function consumeSaleItem(
        Product $product,
        float $quantity,
        ?int $warehouseId,
        ?int $locationId,
        Order $order,
        ?int $userId = null,
        ?int $restrictWarehouseId = null
    ): void {
        if ($quantity <= 0) {
            return;
        }

        if ($product->is_prepared) {
            self::consumePreparedProduct($product, $quantity, $userId, $order);
            return;
        }

        if ($restrictWarehouseId && $warehouseId && (int) $warehouseId !== $restrictWarehouseId) {
            $warehouseId = null;
            $locationId = null;
        }

        $stockItem = null;
        if ($warehouseId && $locationId) {
            $stockItem = StockItem::where('product_id', $product->id)
                ->where('warehouse_id', $warehouseId)
                ->where('location_id', $locationId)
                ->lockForUpdate()
                ->first();
        }

        if (!$stockItem) {
            $stockItem = StockItem::where('product_id', $product->id)
                ->when($restrictWarehouseId, fn ($q) => $q->where('warehouse_id', $restrictWarehouseId))
                ->orderByDesc('quantity')
                ->lockForUpdate()
                ->first();
        }

        // Productos sin existencias registradas solo se descuentan si controlan inventario
        if (!$stockItem) {
            if (!$product->is_stock_tracked) {
                return;
            }

            if (!$warehouseId || !$locationId) {
                [$warehouseId, $locationId] = self::defaultLocation($restrictWarehouseId);
            }

            $stockItem = self::newStockItem($product, $warehouseId, $locationId);
        }

        $unitCost = (float) $stockItem->average_cost;
        $newQty = max(0, (float) $stockItem->quantity - $quantity);

        $stockItem->quantity = $newQty;
        $stockItem->save();

        StockMovement::create([
            'product_id' => $product->id,
            'warehouse_id' => $stockItem->warehouse_id,
            'location_id' => $stockItem->location_id,
            'unit_id' => $stockItem->unit_id,
            'movement_date' => now(),
            'type' => 'out',
            'reason' => 'sale',
            'quantity' => -1 * $quantity,
            'unit' => $stockItem->unit,
            'unit_cost' => $unitCost,
            'total_cost' => -1 * $quantity * $unitCost,
            'running_quantity' => $newQty,
            'running_average_cost' => $unitCost,
            'reference_type' => 'order',
            'reference_id' => $order->id,
            'performed_by' => $userId,
        ]);

        self::syncStockQuantity($product);
    }

    /**
     * Fija la existencia total de un producto (formulario de variantes, importación CSV).
     * La diferencia contra el stock actual se registra como ajuste en el kardex, en la
     * ubicación con más existencias o, si no tiene ninguna, en el depósito principal.
     */
    public static function setProductQuantity(Product $product, float $target, string $reason, ?int $userId = null): void
    {
        $target = max(0, $target);

        DB::transaction(function () use ($product, $target, $reason, $userId) {
            $current = (float) StockItem::where('product_id', $product->id)->lockForUpdate()->sum('quantity');
            $diff = round($target - $current, 3);

            if ($diff != 0) {
                $stockItem = StockItem::where('product_id', $product->id)->orderByDesc('quantity')->first();
                if (!$stockItem) {
                    [$warehouseId, $locationId] = self::defaultLocation();
                    $stockItem = self::newStockItem($product, $warehouseId, $locationId);
                    $stockItem->average_cost = (float) ($product->cost ?? 0);
                }

                $unitCost = (float) $stockItem->average_cost;
                $newQty = max(0, (float) $stockItem->quantity + $diff);
                $stockItem->quantity = $newQty;
                $stockItem->save();

                StockMovement::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $stockItem->warehouse_id,
                    'location_id' => $stockItem->location_id,
                    'unit_id' => $stockItem->unit_id,
                    'movement_date' => now(),
                    'type' => 'adjustment',
                    'reason' => $reason,
                    'quantity' => $diff,
                    'unit' => $stockItem->unit,
                    'unit_cost' => $unitCost,
                    'total_cost' => $diff * $unitCost,
                    'running_quantity' => $newQty,
                    'running_average_cost' => $unitCost,
                    'reference_type' => 'product',
                    'reference_id' => $product->id,
                    'performed_by' => $userId,
                ]);
            }

            self::syncStockQuantity($product);
        });
    }

    /**
     * products.stock_quantity es un resumen: la suma de las existencias por ubicación.
     */
    public static function syncStockQuantity(Product $product): void
    {
        $total = (float) StockItem::where('product_id', $product->id)->sum('quantity');
        Product::whereKey($product->id)->update(['stock_quantity' => $total]);
        $product->stock_quantity = $total;
    }

    /**
     * Depósito y ubicación por defecto: el depósito indicado o el principal (código MAIN),
     * y su primera ubicación activa. Los crea si no existen.
     *
     * @return array{0:int,1:int}
     */
    public static function defaultLocation(?int $warehouseId = null): array
    {
        $warehouse = $warehouseId
            ? Warehouse::find($warehouseId)
            : (Warehouse::where('code', 'MAIN')->first()
                ?? Warehouse::where('is_active', true)->orderBy('sort_order')->orderBy('id')->first());

        if (!$warehouse) {
            $warehouse = Warehouse::create(['code' => 'MAIN', 'name' => 'Almacén principal', 'is_active' => true]);
        }

        $location = Location::where('warehouse_id', $warehouse->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first()
            ?? Location::firstOrCreate(
                ['warehouse_id' => $warehouse->id, 'code' => 'GEN'],
                ['name' => 'General', 'is_active' => true]
            );

        return [$warehouse->id, $location->id];
    }

    protected static function newStockItem(Product $product, ?int $warehouseId, ?int $locationId): StockItem
    {
        return StockItem::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouseId,
            'location_id' => $locationId,
            'unit_id' => $product->stock_unit_id,
            'quantity' => 0,
            'unit' => $product->default_unit ?: null,
            'average_cost' => 0,
            'min_quantity' => 0,
        ]);
    }

    /**
     * Registra una entrada de inventario asociada a una factura de proveedor.
     */
    public static function recordPurchaseEntry(
        Product $product,
        float $quantity,
        float $unitCost,
        SupplierInvoice $invoice,
        ?int $userId = null,
        ?int $unitIdOverride = null,
        ?string $unitOverride = null
    ): void {
        $quantity = (float) $quantity;

        if ($quantity <= 0 || !$product->is_stock_tracked) {
            return;
        }

        self::applyStockChange(
            product: $product,
            quantity: $quantity,
            reason: 'purchase',
            type: 'in',
            userId: $userId,
            referenceType: 'supplier_invoice',
            referenceId: $invoice->id,
            unitOverride: $unitOverride,
            explicitUnitCost: $unitCost,
            unitIdOverride: $unitIdOverride
        );
    }

    protected static function consumePreparedProduct(Product $product, float $soldQty, ?int $userId, Order $order): void
    {
        $recipe = Recipe::with('items.component')->where('product_id', $product->id)->first();
        if (!$recipe || $recipe->yield_quantity <= 0) {
            return; // sin receta válida, no se descuenta nada por seguridad
        }

        $factor = $soldQty / (float) $recipe->yield_quantity;

        foreach ($recipe->items as $recipeItem) {
            $baseQty = (float) $recipeItem->quantity * $factor;
            $wastageMultiplier = 1 + ((float) $recipeItem->wastage_percent / 100.0);
            $consumptionQty = $baseQty * $wastageMultiplier;

            $component = $recipeItem->component;
            if (!$component) {
                continue;
            }

            if (!$component->is_stock_tracked) {
                continue;
            }

            self::applyStockChange(
                product: $component,
                quantity: -1 * $consumptionQty,
                reason: 'sale_recipe',
                type: 'out',
                userId: $userId,
                referenceType: 'order',
                referenceId: $order->id,
                unitOverride: $recipeItem->unit ?: null,
            );
        }
    }

    /**
     * Aplica cambio de stock con costo promedio móvil.
     * quantity puede ser positiva (entrada) o negativa (salida).
     */
    protected static function applyStockChange(
        Product $product,
        float $quantity,
        string $reason,
        string $type,
        ?int $userId,
        string $referenceType,
        ?int $referenceId,
        ?string $unitOverride = null,
        ?float $explicitUnitCost = null,
        ?int $unitIdOverride = null,
    ): void {
        $stockItem = StockItem::firstOrCreate(
            ['product_id' => $product->id],
            [
                'unit_id' => $product->stock_unit_id,
                'quantity' => 0,
                'unit' => $product->default_unit ?: null,
                'average_cost' => 0,
                'min_quantity' => 0,
            ]
        );

        $oldQty = (float) $stockItem->quantity;
        $oldAvg = (float) $stockItem->average_cost;

        $effectiveQty = $quantity; // ya viene con signo

        // Entradas vs salidas
        if ($type === 'in' && $effectiveQty > 0) {
            $unitCost = $explicitUnitCost ?? $oldAvg;
            $newQty = $oldQty + $effectiveQty;
            if ($newQty <= 0) {
                $newAvg = 0;
            } else {
                $newAvg = ($oldQty * $oldAvg + $effectiveQty * $unitCost) / $newQty;
            }
        } else {
            // Salidas al costo promedio
            $unitCost = $explicitUnitCost ?? $oldAvg;
            $newQty = $oldQty + $effectiveQty;
            if ($newQty < 0) {
                $newQty = 0; // evitar negativos duros
            }
            $newAvg = $oldAvg;
        }

        $stockItem->quantity = $newQty;
        $stockItem->average_cost = $newAvg;
        $stockItem->unit_id = $unitIdOverride ?? $stockItem->unit_id ?? $product->stock_unit_id;
        $stockItem->unit = $unitOverride ?: ($stockItem->unit ?: $product->default_unit ?: null);
        $stockItem->save();

        $totalCost = $effectiveQty * $unitCost;

        StockMovement::create([
            'product_id' => $product->id,
            'unit_id' => $stockItem->unit_id,
            'movement_date' => now(),
            'type' => $type,
            'reason' => $reason,
            'quantity' => $effectiveQty,
            'unit' => $stockItem->unit,
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
            'running_quantity' => $newQty,
            'running_average_cost' => $newAvg,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'performed_by' => $userId,
        ]);

        self::syncStockQuantity($product);
    }
}
