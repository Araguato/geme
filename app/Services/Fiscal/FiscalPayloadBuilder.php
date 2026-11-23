<?php

namespace App\Services\Fiscal;

use App\Models\Order;
use App\Models\Setting;

/**
 * Datos de la factura con lo que exige la Providencia SNAT/2026/00093: emisor, adquirente,
 * fecha y hora, líneas con marca (E), base e IVA por alícuota, y el equivalente en Bs con la tasa.
 * Es neutro respecto a la imprenta: cada driver lo traduce a su formato.
 */
class FiscalPayloadBuilder
{
    public static function build(Order $order, string $documentType = 'FACTURA'): array
    {
        $order->loadMissing(['items', 'customerParty']);

        $party = $order->customerParty;
        $taxId = RifValidator::parse($party?->document_number);

        $lines = [];
        $byRate = [];
        $exempt = 0.0;
        $subtotal = 0.0;
        $taxTotal = 0.0;

        foreach ($order->items as $item) {
            $base = round((float) $item->quantity * (float) $item->unit_price, 2);
            $tax = round((float) $item->tax_amount, 2);
            $rate = (float) $item->tax_rate;
            $isExempt = $rate <= 0 || $tax <= 0;

            $lines[] = [
                'description' => $item->name,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'tax_rate' => $isExempt ? 0.0 : $rate,
                'exempt' => $isExempt, // se imprime la marca (E)
                'base' => $base,
                'tax_amount' => $isExempt ? 0.0 : $tax,
                'total' => round($base + ($isExempt ? 0 : $tax), 2),
            ];

            $subtotal += $base;
            if ($isExempt) {
                $exempt += $base;
            } else {
                $key = number_format($rate, 2, '.', '');
                $byRate[$key]['rate'] = $rate;
                $byRate[$key]['base'] = round(($byRate[$key]['base'] ?? 0) + $base, 2);
                $byRate[$key]['tax'] = round(($byRate[$key]['tax'] ?? 0) + $tax, 2);
                $taxTotal += $tax;
            }
        }

        $total = round($subtotal + $taxTotal, 2);
        $rate = (float) ($order->exchange_rate ?? 0);

        return [
            'document_type' => $documentType,
            'order_number' => $order->order_number,
            'issued_at' => optional($order->invoiced_at ?? $order->created_at)->toIso8601String(), // fecha y hora
            'issuer' => [
                'tax_id' => Setting::get('company_tax_id'),
                'name' => Setting::get('company_name'),
                'address' => Setting::get('company_address'),
            ],
            'buyer' => [
                'name' => $party?->name ?? $order->customer_name ?: 'CONSUMIDOR FINAL',
                'tax_id' => $taxId['normalized'] ?? null,
                'tax_id_valid' => $taxId !== null,
            ],
            'items' => $lines,
            'exempt_amount' => round($exempt, 2),
            'tax_by_rate' => array_values($byRate),
            'subtotal' => round($subtotal, 2),
            'tax_total' => round($taxTotal, 2),
            'total' => $total,
            'currency' => 'USD',
            'exchange_rate' => $rate ?: null,
            'total_bs' => $rate > 0 ? round($total * $rate, 2) : null,
            'affected_document_number' => $order->affected_document_number,
        ];
    }
}
