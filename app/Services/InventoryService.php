<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\SupplierInvoice;
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
    }
}
