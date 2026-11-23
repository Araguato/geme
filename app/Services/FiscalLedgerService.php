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
        $documentDate = $invoice->date;

        $taxRate = 0.0;
        if ($invoice->taxable_amount > 0 && $invoice->total_tax > 0) {
            $taxRate = round(($invoice->total_tax / $invoice->taxable_amount) * 100, 2);
        }

        $entry = FiscalLedger::updateOrCreate([
            'entry_type' => 'entrada',
            'related_id' => $invoice->id,
            'related_type' => SupplierInvoice::class,
        ], array_merge(self::companyFields($documentDate), [
            'entry_type' => 'entrada',
            'document_date' => $documentDate,
            'document_type' => self::normalizeDocumentType($invoice->doc_type),
            'document_number' => $invoice->invoice_number,
            'control_number' => $invoice->control_number,
            'affected_document_number' => $invoice->affected_document,
            'partner_name' => $supplierParty?->name,
            'partner_tax_id' => $supplierParty?->document_number,
            'currency' => $invoice->currency,
            'exchange_rate' => $invoice->bcv_rate_at_issue,
            'taxable_amount' => (float) ($invoice->taxable_amount ?? 0),
            'exempt_amount' => (float) ($invoice->exempt_amount ?? 0),
            'exonerated_amount' => (float) Arr::get($invoice->metadata, 'exonerated_amount', 0),
            'non_subject_amount' => (float) Arr::get($invoice->metadata, 'non_subject_amount', 0),
            'tax_amount' => (float) ($invoice->total_tax ?? 0),
            'tax_rate' => $taxRate,
            'total_amount' => (float) ($invoice->total_amount ?? 0),
            'withholding_amount' => (float) ($invoice->withholding_amount ?? 0),
            'metadata' => [
                'status' => $invoice->status,
                'notes' => $invoice->notes,
                'source' => 'supplier_invoice',
            ],
        ]));

        FiscalIntegrityService::recalculateLedgerChainFrom($entry->id);

        return $entry;
    }

    public static function recordSale(Order $order, array $metadata = []): ?FiscalLedger
    {
        if (!$order->id) {
            return null;
        }

        $order->loadMissing(['items', 'customerParty', 'fiscalDocument']);

        $taxable = 0.0;
        $exempt = 0.0;
        $tax = 0.0;
        $total = 0.0;
        $taxRates = [];

        foreach ($order->items as $item) {
            /** @var OrderItem $item */
            $lineTotal = (float) ($item->subtotal ?? 0);
            $taxAmount = (float) ($item->tax_amount ?? 0);
            $baseAmount = max($lineTotal - $taxAmount, 0);
            $rate = (float) ($item->tax_rate ?? 0);

            if ($taxAmount > 0) {
                $taxable += $baseAmount;
                if ($rate > 0) {
                    $taxRates[$rate] = ($taxRates[$rate] ?? 0) + $taxAmount;
                }
            } else {
                $exempt += $lineTotal;
            }

            $tax += $taxAmount;
            $total += $lineTotal;
        }

        if ($total <= 0 && $order->total) {
            $total = (float) $order->total;
        }

        $taxRate = 0.0;
        if (count($taxRates) === 1) {
            $taxRate = array_key_first($taxRates);
        } elseif ($taxable > 0 && $tax > 0) {
            $taxRate = round(($tax / $taxable) * 100, 2);
        }

        $documentDate = optional($order->created_at)->toDateString();
        $currency = Arr::get($metadata, 'currency', 'USD');
        $exchangeRate = Arr::get($metadata, 'exchange_rate', (float) Setting::get('bcv_rate', 0));

        $customerParty = $order->customerParty;
        $customerName = $customerParty?->name
            ?? $order->customer_name
            ?? 'CONSUMIDOR FINAL';

        $customerTaxId = Arr::get($metadata, 'customer_tax_id');
        if (!$customerTaxId) {
            $customerTaxId = $customerParty?->document_number
                ?? $order->customer_name
                ?? 'V000000000';
        }

        $documentType = $order->document_type ?? 'FACTURA';
        $documentNumber = $order->external_invoice_number ?? $order->order_number;
        // Con medio autorizado, el número de control lo asigna la imprenta
        $controlNumber = $order->fiscalDocument?->control_number ?: $order->order_number;

        $entry = FiscalLedger::updateOrCreate([
            'entry_type' => 'salida',
            'related_id' => $order->id,
            'related_type' => Order::class,
        ], array_merge(self::companyFields($documentDate), [
            'entry_type' => 'salida',
            'document_date' => $documentDate,
            'document_type' => self::normalizeDocumentType($documentType),
            'document_number' => $documentNumber,
            'control_number' => $controlNumber,
            'affected_document_number' => $order->affected_document_number,
            'partner_name' => $customerName,
            'partner_tax_id' => $customerTaxId,
            'currency' => $currency,
            'exchange_rate' => $exchangeRate,
            'taxable_amount' => $taxable,
            'exempt_amount' => $exempt,
            'exonerated_amount' => (float) Arr::get($metadata, 'exonerated_amount', 0),
            'non_subject_amount' => (float) Arr::get($metadata, 'non_subject_amount', 0),
            'tax_amount' => $tax,
            'tax_rate' => $taxRate,
            'total_amount' => $total,
            'withholding_amount' => (float) Arr::get($metadata, 'withholding_amount', 0),
            'metadata' => array_merge($metadata, [
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'source' => 'order',
            ]),
        ]));

        FiscalIntegrityService::recalculateLedgerChainFrom($entry->id);

        return $entry;
    }

    /**
     * Asiento de la nota de crédito que anula una venta: documento aparte (NOTA_CREDITO)
     * con los mismos importes y el documento afectado. El asiento de la factura no se toca.
     */
    public static function recordCreditNote(\App\Models\CreditNote $note, Order $order, ?FiscalLedger $original = null): FiscalLedger
    {
        $original ??= FiscalLedger::where('entry_type', 'salida')
            ->where('related_type', Order::class)
            ->where('related_id', $order->id)
            ->first();

        $documentDate = now()->toDateString();

        $entry = FiscalLedger::updateOrCreate([
            'entry_type' => 'salida',
            'related_id' => $note->id,
            'related_type' => \App\Models\CreditNote::class,
        ], array_merge(self::companyFields($documentDate), [
            'entry_type' => 'salida',
            'document_date' => $documentDate,
            'document_type' => 'NOTA_CREDITO',
            'document_number' => $note->number,
            'control_number' => $note->number,
            'affected_document_number' => $note->affected_document_number,
            'partner_name' => $original?->partner_name ?? ($note->customer_name ?: 'CONSUMIDOR FINAL'),
            'partner_tax_id' => $original?->partner_tax_id ?? 'V000000000',
            'currency' => $original?->currency ?? 'USD',
            'exchange_rate' => $original?->exchange_rate ?? $order->exchange_rate,
            'taxable_amount' => (float) ($original?->taxable_amount ?? 0),
            'exempt_amount' => (float) ($original?->exempt_amount ?? 0),
            'exonerated_amount' => (float) ($original?->exonerated_amount ?? 0),
            'non_subject_amount' => (float) ($original?->non_subject_amount ?? 0),
            'tax_amount' => (float) ($original?->tax_amount ?? $order->tax),
            'tax_rate' => $original?->tax_rate,
            'total_amount' => (float) ($original?->total_amount ?? $note->amount),
            'withholding_amount' => 0,
            'metadata' => [
                'source' => 'order_void',
                'reason' => $note->reason,
                'order_number' => $order->order_number,
            ],
        ]));

        FiscalIntegrityService::recalculateLedgerChainFrom($entry->id);

        return $entry;
    }

    private static function companyFields(?string $documentDate): array
    {
        return [
            'period' => $documentDate ? substr($documentDate, 0, 7) : null,
            'company_tax_id' => Setting::get('company_tax_id', 'J000000000'),
            'company_name' => Setting::get('company_name', 'EMPRESA SIN NOMBRE'),
        ];
    }

    private static function normalizeDocumentType(?string $type): string
    {
        $type = strtoupper((string) $type);

        return match ($type) {
            'FACTURA', '01' => 'FACTURA',
            'NOTA_DEBITO', 'NOTADEBITO', 'NOTA DE DEBITO', '02' => 'NOTA_DEBITO',
            'NOTA_CREDITO', 'NOTACREDITO', 'NOTA DE CREDITO', '03' => 'NOTA_CREDITO',
            default => $type ?: 'FACTURA',
        };
    }
}
