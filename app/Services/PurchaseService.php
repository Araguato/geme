<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SupplierInvoice;
use App\Models\SupplierInvoiceItem;
use App\Services\FiscalLedgerService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    /**
     * Crea o actualiza una factura de proveedor con sus renglones y movimientos de inventario.
     */
    public static function saveInvoiceWithItems(
        array $invoiceData,
        array $itemsData,
        ?int $userId = null,
        ?SupplierInvoice $existingInvoice = null
    ): SupplierInvoice {
        return DB::transaction(function () use ($invoiceData, $itemsData, $userId, $existingInvoice) {
            $invoice = $existingInvoice ?? new SupplierInvoice();
            $invoice->fill($invoiceData);
            $invoice->save();

            if ($existingInvoice) {
                $invoice->items()->delete();
            }

            $itemsCollection = collect();

            foreach ($itemsData as $item) {
                $productId = Arr::get($item, 'product_id');
                $quantity = (float) Arr::get($item, 'quantity', 0);
                $unitCost = (float) Arr::get($item, 'unit_cost', 0);
                $taxRate = (float) Arr::get($item, 'tax_rate', 0);
                $taxAmount = (float) Arr::get($item, 'tax_amount', $quantity * $unitCost * $taxRate);
                $subtotal = (float) Arr::get($item, 'subtotal', $quantity * $unitCost);
                $total = (float) Arr::get($item, 'total', $subtotal + $taxAmount);

                $invoiceItem = SupplierInvoiceItem::create([
                    'supplier_invoice_id' => $invoice->id,
                    'product_id' => $productId,
                    'description' => Arr::get($item, 'description'),
                    'unit' => Arr::get($item, 'unit'),
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $taxAmount,
                    'subtotal' => $subtotal,
                    'total' => $total,
                    'metadata' => Arr::get($item, 'metadata', []),
                ]);

                $itemsCollection->push($invoiceItem);

                if ($productId) {
                    $product = Product::find($productId);
                    if ($product && $product->is_stock_tracked) {
                        InventoryService::recordPurchaseEntry(
                            product: $product,
                            quantity: $quantity,
                            unitCost: $unitCost,
                            invoice: $invoice,
                            userId: $userId,
                            unitOverride: $invoiceItem->unit,
                        );
                    }
                }
            }

            $invoice->setRelation('items', $itemsCollection);
            $invoice->recalculateTotals();
            $invoice->save();

            FiscalLedgerService::recordPurchase($invoice);

            return $invoice;
        });
    }
}
