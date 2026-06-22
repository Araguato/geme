<?php

namespace App\Services;

use App\Models\FiscalLedger;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\SupplierInvoice;
use Illuminate\Support\Arr;

class FiscalLedgerService
{
    public static function recordPurchase(SupplierInvoice $invoice): FiscalLedger
    {
        $supplierParty = $invoice->supplier?->party;

        $entry = FiscalLedger::updateOrCreate([
            'entry_type' => 'entrada',
            'related_id' => $invoice->id,
            'related_type' => SupplierInvoice::class,
        ], [
            'entry_type' => 'entrada',
            'document_date' => $invoice->date,
            'document_type' => $invoice->doc_type,
            'document_number' => $invoice->invoice_number,
            'control_number' => $invoice->control_number,
            'reference_number' => $invoice->affected_document,
            'partner_name' => $supplierParty?->name,
            'partner_tax_id' => $supplierParty?->document_number,
            'currency' => $invoice->currency,
            'exchange_rate' => $invoice->bcv_rate_at_issue,
            'taxable_amount' => (float) ($invoice->taxable_amount ?? 0),
            'exempt_amount' => (float) ($invoice->exempt_amount ?? 0),
            'tax_amount' => (float) ($invoice->total_tax ?? 0),
            'total_amount' => (float) ($invoice->total_amount ?? 0),
            'withholding_amount' => (float) ($invoice->withholding_amount ?? 0),
            'metadata' => [
                'status' => $invoice->status,
                'notes' => $invoice->notes,
                'source' => 'supplier_invoice',
            ],
        ]);

        FiscalIntegrityService::recalculateLedgerChainFrom($entry->id);

        return $entry;
    }

    public static function recordSale(Order $order, array $metadata = []): ?FiscalLedger
    {
        if (!$order->id) {
            return null;
        }

        $order->loadMissing('items');

        $taxable = 0.0;
        $exempt = 0.0;
        $tax = 0.0;
        $total = 0.0;

        foreach ($order->items as $item) {
            /** @var OrderItem $item */
            $lineTotal = (float) ($item->subtotal ?? 0);
            $taxAmount = (float) ($item->tax_amount ?? 0);
            $baseAmount = max($lineTotal - $taxAmount, 0);

            if ($taxAmount > 0) {
                $taxable += $baseAmount;
            } else {
                $exempt += $lineTotal;
            }

            $tax += $taxAmount;
            $total += $lineTotal;
        }

        if ($total <= 0 && $order->total) {
            $total = (float) $order->total;
        }

        $currency = Arr::get($metadata, 'currency', 'USD');
        $exchangeRate = Arr::get($metadata, 'exchange_rate', (float) Setting::get('bcv_rate', 0));

        $customerName = $order->customer?->name
            ?? $order->account_label
            ?? 'CONSUMIDOR FINAL';

        $customerTaxId = Arr::get($metadata, 'customer_tax_id');
        if (!$customerTaxId) {
            $customerTaxId = $order->customer?->document_number
                ?? $order->account_label
                ?? 'V000000000';
        }

        $entry = FiscalLedger::updateOrCreate([
            'entry_type' => 'salida',
            'related_id' => $order->id,
            'related_type' => Order::class,
        ], [
            'entry_type' => 'salida',
            'document_date' => optional($order->created_at)->toDateString(),
            'document_type' => $order->document_type ?? 'FACTURA',
            'document_number' => $order->external_invoice_number ?? $order->order_number,
            'control_number' => $order->order_number,
            'reference_number' => $order->account_label,
            'partner_name' => $customerName,
            'partner_tax_id' => $customerTaxId,
            'currency' => $currency,
            'exchange_rate' => $exchangeRate,
            'taxable_amount' => $taxable,
            'exempt_amount' => $exempt,
            'tax_amount' => $tax,
            'total_amount' => $total,
            'withholding_amount' => (float) Arr::get($metadata, 'withholding_amount', 0),
            'metadata' => array_merge($metadata, [
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'source' => 'order',
            ]),
        ]);

        FiscalIntegrityService::recalculateLedgerChainFrom($entry->id);

        return $entry;
    }
}
