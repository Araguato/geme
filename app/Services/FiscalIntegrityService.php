<?php

namespace App\Services;

use App\Models\Order;
use App\Models\CreditNote;
use App\Models\FiscalLedger;

class FiscalIntegrityService
{
    /**
     * Encadena y firma una orden finalizada.
     */
    public static function signFinalOrder(Order $order): void
    {
        if (!$order->id) {
            return;
        }

        $previousHash = Order::whereNotNull('hash_actual')
            ->orderByDesc('id')
            ->value('hash_actual');

        $payload = [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'total' => (float) ($order->total ?? 0),
            'total_tax' => (float) ($order->total_tax ?? 0),
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'created_at' => optional($order->created_at)->toIso8601String(),
            'updated_at' => optional($order->updated_at)->toIso8601String(),
        ];

        $hashBase = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $hashActual = hash('sha256', ($previousHash ?? '') . '|' . $hashBase);

        $order->hash_anterior = $previousHash;
        $order->hash_actual = $hashActual;
        $order->save();
    }

    /**
     * Encadena y firma una nota de crédito emitida.
     */
    public static function signCreditNote(CreditNote $creditNote): void
    {
        if (!$creditNote->id) {
            return;
        }

        $previousHash = CreditNote::whereNotNull('hash_actual')
            ->orderByDesc('id')
            ->value('hash_actual');

        $payload = [
            'id' => $creditNote->id,
            'number' => $creditNote->number,
            'order_id' => $creditNote->order_id,
            'total' => (float) ($creditNote->total ?? 0),
            'reason' => $creditNote->reason,
            'created_at' => optional($creditNote->created_at)->toIso8601String(),
            'status' => $creditNote->status,
        ];

        $hashBase = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $hashActual = hash('sha256', ($previousHash ?? '') . '|' . $hashBase);

        $creditNote->hash_anterior = $previousHash;
        $creditNote->hash_actual = $hashActual;
        $creditNote->save();
    }

    public static function recalculateLedgerChainFrom(?int $startId = null): void
    {
        $query = FiscalLedger::orderBy('id');

        if ($startId) {
            $query->where('id', '>=', $startId);
            $previousHash = FiscalLedger::where('id', '<', $startId)
                ->whereNotNull('fiscal_hash')
                ->orderByDesc('id')
                ->value('fiscal_hash');
        } else {
            $previousHash = null;
        }

        $entries = $query->get();

        foreach ($entries as $entry) {
            $payload = [
                'id' => $entry->id,
                'entry_type' => $entry->entry_type,
                'document_date' => optional($entry->document_date)->toDateString(),
                'document_number' => $entry->document_number,
                'control_number' => $entry->control_number,
                'partner_tax_id' => $entry->partner_tax_id,
                'taxable_amount' => (float) ($entry->taxable_amount ?? 0),
                'exempt_amount' => (float) ($entry->exempt_amount ?? 0),
                'tax_amount' => (float) ($entry->tax_amount ?? 0),
                'total_amount' => (float) ($entry->total_amount ?? 0),
                'withholding_amount' => (float) ($entry->withholding_amount ?? 0),
                'created_at' => optional($entry->created_at)->toIso8601String(),
            ];

            $hashBase = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $hashActual = hash('sha256', ($previousHash ?? '') . '|' . $hashBase);

            $entry->previous_hash = $previousHash;
            $entry->fiscal_hash = $hashActual;
            $entry->save();

            $previousHash = $hashActual;
        }
    }
}
