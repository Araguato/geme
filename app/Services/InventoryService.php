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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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
                    self::takeFromRows(self::stockRows($product), (float) $item->quantity, $product, 'sale', $order, $userId);
                }
            }
        });
    }

    /**
     * Descuenta del inventario una línea vendida en el TPV.
     * Empieza por el depósito/ubicación elegidos y sigue por las demás ubicaciones.
     * Con $restrictWarehouseId (depósito del negocio donde está abierta la caja) solo
     * usa ese depósito. Si el producto controla stock y no alcanza, rechaza la venta.
     * Devuelve la primera fila de stock usada (null si no se descontó nada).
     */
    public static function consumeSaleItem(
        Product $product,
        float $quantity,
        ?int $warehouseId,
        ?int $locationId,
        Order $order,
        ?int $userId = null,
        ?int $restrictWarehouseId = null
    ): ?StockItem {
        if ($quantity <= 0) {
            return null;
        }

        if ($product->is_prepared) {
            self::consumePreparedProduct($product, $quantity, $userId, $order, $restrictWarehouseId);
            return null;
        }

        $rows = self::stockRows($product, $restrictWarehouseId, $warehouseId, $locationId);

        // Productos sin existencias registradas solo se validan si controlan inventario
        if (!$product->is_stock_tracked && !StockItem::where('product_id', $product->id)->exists()) {
            return null;
        }

        $available = round((float) $rows->sum('quantity'), 3);
        if (round($quantity, 3) > $available) {
            $elsewhere = round((float) StockItem::where('product_id', $product->id)->sum('quantity') - $available, 3);
            $message = "{$product->name}: hay {$available} disponibles y se piden {$quantity}.";
            if ($elsewhere > 0) {
                $message .= " Hay {$elsewhere} en otros depósitos: transfiérelos antes de vender.";
            }

            throw ValidationException::withMessages(['items' => $message]);
        }

        return self::takeFromRows($rows, $quantity, $product, 'sale', $order, $userId);
    }

    /**
     * Filas de stock del producto, bloqueadas para actualizar: primero la ubicación
     * preferida y luego las demás de mayor a menor existencia.
     */
    protected static function stockRows(
        Product $product,
        ?int $restrictWarehouseId = null,
        ?int $preferredWarehouseId = null,
        ?int $preferredLocationId = null
    ): Collection {
        $rows = StockItem::where('product_id', $product->id)
            ->when($restrictWarehouseId, fn ($q) => $q->where('warehouse_id', $restrictWarehouseId))
            ->orderByDesc('quantity')
            ->lockForUpdate()
            ->get();

        [$preferred, $others] = $rows->partition(
            fn (StockItem $row) => $preferredWarehouseId
                && (int) $row->warehouse_id === $preferredWarehouseId
                && (int) $row->location_id === $preferredLocationId
        );

        return $preferred->concat($others)->values();
    }

    /**
     * Saca la cantidad de las filas en orden y registra un movimiento por cada una.
     * Devuelve la primera fila usada.
     */
    protected static function takeFromRows(
        Collection $rows,
        float $quantity,
        Product $product,
        string $reason,
        Order $order,
        ?int $userId,
        ?string $unit = null
    ): ?StockItem {
        $remaining = round($quantity, 3);
        $first = null;

        foreach ($rows as $row) {
            /** @var StockItem $row */
            $take = min($remaining, (float) $row->quantity);
            if ($take <= 0) {
                continue;
            }

            $unitCost = (float) $row->average_cost;
            $newQty = round((float) $row->quantity - $take, 3);
            $row->quantity = $newQty;
            $row->unit = $unit ?: $row->unit;
            $row->save();

            StockMovement::create([
                'product_id' => $product->id,
                'warehouse_id' => $row->warehouse_id,
                'location_id' => $row->location_id,
                'unit_id' => $row->unit_id,
                'movement_date' => now(),
                'type' => 'out',
                'reason' => $reason,
                'quantity' => -1 * $take,
                'unit' => $row->unit,
                'unit_cost' => $unitCost,
                'total_cost' => -1 * $take * $unitCost,
                'running_quantity' => $newQty,
                'running_average_cost' => $unitCost,
                'reference_type' => 'order',
                'reference_id' => $order->id,
                'performed_by' => $userId,
            ]);

            $first ??= $row;
            $remaining = round($remaining - $take, 3);
            if ($remaining <= 0) {
                break;
            }
        }

        if ($remaining > 0) {
            Log::warning('Stock insuficiente al descontar', [
                'product_id' => $product->id,
                'order_id' => $order->id,
                'faltante' => $remaining,
            ]);
        }

        self::syncStockQuantity($product);

        return $first;
    }

    /**
     * Fija la existencia total de un producto (formulario de variantes, importación CSV).
     * La diferencia contra el stock actual se registra como ajuste en el kardex, en las
     * ubicaciones con más existencias o, si no tiene ninguna, en el depósito principal.
     */
    public static function setProductQuantity(Product $product, float $target, string $reason, ?int $userId = null): void
    {
        $target = max(0, $target);

        DB::transaction(function () use ($product, $target, $reason, $userId) {
            $current = (float) StockItem::where('product_id', $product->id)->lockForUpdate()->sum('quantity');
            $diff = round($target - $current, 3);

            $rows = StockItem::where('product_id', $product->id)->orderByDesc('quantity')->get();

            if ($diff > 0 && $rows->isEmpty()) {
                [$warehouseId, $locationId] = self::defaultLocation();
                $stockItem = self::newStockItem($product, $warehouseId, $locationId);
                $stockItem->average_cost = (float) ($product->cost ?? 0);
                $rows = collect([$stockItem]);
            }

            foreach ($rows as $stockItem) {
                if ($diff == 0) {
                    break;
                }

                // Una entrada va a la primera fila; una reducción se reparte entre todas
                $change = $diff > 0 ? $diff : -1 * min(-1 * $diff, (float) $stockItem->quantity);
                if ($change == 0) {
                    continue;
                }

                $unitCost = (float) $stockItem->average_cost;
                $newQty = (float) $stockItem->quantity + $change;
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
                    'quantity' => $change,
                    'unit' => $stockItem->unit,
                    'unit_cost' => $unitCost,
                    'total_cost' => $change * $unitCost,
                    'running_quantity' => $newQty,
                    'running_average_cost' => $unitCost,
                    'reference_type' => 'product',
                    'reference_id' => $product->id,
                    'performed_by' => $userId,
                ]);

                $diff = round($diff - $change, 3);
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
     * Registra una entrada de inventario asociada a una factura de proveedor, en el
     * depósito indicado o, si no se indica, en el principal.
     */
    public static function recordPurchaseEntry(
        Product $product,
        float $quantity,
        float $unitCost,
        SupplierInvoice $invoice,
        ?int $userId = null,
        ?int $unitIdOverride = null,
        ?string $unitOverride = null,
        ?int $warehouseId = null
    ): void {
        $quantity = (float) $quantity;

        if ($quantity <= 0 || !$product->is_stock_tracked) {
            return;
        }

        self::applyPurchase($product, $quantity, $unitCost, $invoice, $userId, $unitIdOverride, $unitOverride, $warehouseId);
    }

    /**
     * Revierte lo que una factura sumó al inventario, antes de registrar sus renglones
     * editados. Una fila puede quedar en negativo si ya se vendió parte de la mercancía;
     * assertPurchaseEditFits comprueba el resultado al terminar.
     * Devuelve los productos revertidos.
     *
     * @return array<int, int>
     */
    public static function reversePurchaseEntries(SupplierInvoice $invoice, ?int $userId = null): array
    {
        $entries = StockMovement::where('reference_type', 'supplier_invoice')
            ->where('reference_id', $invoice->id)
            ->get()
            ->groupBy(fn (StockMovement $m) => $m->product_id . '-' . $m->warehouse_id . '-' . $m->location_id);

        $productIds = [];

        foreach ($entries as $movements) {
            $net = round((float) $movements->sum('quantity'), 3);
            if ($net <= 0) {
                continue;
            }

            $entry = $movements->first();
            $product = Product::find($entry->product_id);
            if (!$product) {
                continue;
            }

            // Las entradas antiguas no guardaban depósito: se revierten de la fila con más stock
            $query = StockItem::where('product_id', $product->id)->orderByDesc('quantity')->lockForUpdate();
            $stockItem = $entry->warehouse_id
                ? (clone $query)->where('warehouse_id', $entry->warehouse_id)->where('location_id', $entry->location_id)->first()
                : null;
            $stockItem ??= $query->first();
            if (!$stockItem) {
                [$warehouseId, $locationId] = self::defaultLocation($entry->warehouse_id);
                $stockItem = self::newStockItem($product, $warehouseId, $locationId);
            }

            $value = (float) $movements->sum('total_cost');
            $oldQty = (float) $stockItem->quantity;
            $oldAvg = (float) $stockItem->average_cost;
            $newQty = round($oldQty - $net, 3);
            $newAvg = $newQty > 0 ? max(0, ($oldQty * $oldAvg - $value) / $newQty) : $oldAvg;

            $stockItem->quantity = $newQty;
            $stockItem->average_cost = $newAvg;
            $stockItem->save();

            StockMovement::create([
                'product_id' => $product->id,
                'warehouse_id' => $stockItem->warehouse_id,
                'location_id' => $stockItem->location_id,
                'unit_id' => $stockItem->unit_id,
                'movement_date' => now(),
                'type' => 'out',
                'reason' => 'purchase_edit',
                'quantity' => -1 * $net,
                'unit' => $stockItem->unit,
                'unit_cost' => $value / $net,
                'total_cost' => -1 * $value,
                'running_quantity' => $newQty,
                'running_average_cost' => $newAvg,
                'reference_type' => 'supplier_invoice',
                'reference_id' => $invoice->id,
                'performed_by' => $userId,
            ]);

            self::syncStockQuantity($product);
            $productIds[] = $product->id;
        }

        return array_values(array_unique($productIds));
    }

    /**
     * Rechaza la edición si deja un producto en negativo: no se puede quitar de una
     * factura mercancía que ya se vendió o se movió.
     *
     * @param array<int, int> $productIds
     */
    public static function assertPurchaseEditFits(array $productIds): void
    {
        $short = StockItem::whereIn('product_id', $productIds)->where('quantity', '<', 0)->with('product')->first();

        if ($short) {
            $name = $short->product?->name ?? 'Producto';

            throw ValidationException::withMessages([
                'items' => "{$name}: no se puede reducir la cantidad de la factura porque esa mercancía ya se vendió o se movió.",
            ]);
        }
    }

    /**
     * Descuenta los ingredientes de la receta del depósito del negocio. Un ingrediente
     * faltante no frena la venta: se descuenta lo que haya y se deja aviso en el log.
     */
    protected static function consumePreparedProduct(Product $product, float $soldQty, ?int $userId, Order $order, ?int $warehouseId = null): void
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

            if (!$component->is_stock_tracked && !StockItem::where('product_id', $component->id)->exists()) {
                continue;
            }

            self::takeFromRows(
                self::stockRows($component, $warehouseId),
                $consumptionQty,
                $component,
                'sale_recipe',
                $order,
                $userId,
                $recipeItem->unit ?: null,
            );
        }
    }

    /**
     * Suma una compra a la ubicación del depósito que ya tenga el producto, o a la
     * primera del depósito, con costo promedio móvil.
     */
    protected static function applyPurchase(
        Product $product,
        float $quantity,
        float $unitCost,
        SupplierInvoice $invoice,
        ?int $userId,
        ?int $unitIdOverride,
        ?string $unitOverride,
        ?int $warehouseId,
    ): void {
        [$warehouseId, $locationId] = self::defaultLocation($warehouseId);

        $rows = StockItem::where('product_id', $product->id)
            ->where('warehouse_id', $warehouseId)
            ->orderByDesc('quantity')
            ->lockForUpdate()
            ->get();

        // Al editar una factura, la fila que quedó en negativo recibe primero la nueva entrada
        $stockItem = $rows->first(fn (StockItem $row) => (float) $row->quantity < 0)
            ?? $rows->first()
            ?? self::newStockItem($product, $warehouseId, $locationId);

        $oldQty = (float) $stockItem->quantity;
        $oldAvg = (float) $stockItem->average_cost;
        $newQty = $oldQty + $quantity;
        $newAvg = $newQty > 0 ? ($oldQty * $oldAvg + $quantity * $unitCost) / $newQty : $oldAvg;

        $stockItem->quantity = $newQty;
        $stockItem->average_cost = $newAvg;
        $stockItem->unit_id = $unitIdOverride ?? $stockItem->unit_id ?? $product->stock_unit_id;
        $stockItem->unit = $unitOverride ?: ($stockItem->unit ?: $product->default_unit ?: null);
        $stockItem->save();

        StockMovement::create([
            'product_id' => $product->id,
            'warehouse_id' => $stockItem->warehouse_id,
            'location_id' => $stockItem->location_id,
            'unit_id' => $stockItem->unit_id,
            'movement_date' => now(),
            'type' => 'in',
            'reason' => 'purchase',
            'quantity' => $quantity,
            'unit' => $stockItem->unit,
            'unit_cost' => $unitCost,
            'total_cost' => $quantity * $unitCost,
            'running_quantity' => $newQty,
            'running_average_cost' => $newAvg,
            'reference_type' => 'supplier_invoice',
            'reference_id' => $invoice->id,
            'performed_by' => $userId,
        ]);

        self::syncStockQuantity($product);
    }
}
