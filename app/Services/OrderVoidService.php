<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CashShift;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\FiscalDocument;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Anulación de una venta. La venta no se borra ni se edita: queda en estado «cancelled»
 * y se emite una nota de crédito interna que revierte inventario, cobro y libro de ventas,
 * dejando todo auditado (el libro y su cadena de hash no se alteran, solo se añade el asiento).
 */
class OrderVoidService
{
    public static function void(Order $order, User $user, string $reason, ?string $ip = null, ?string $userAgent = null): CreditNote
    {
        $reason = trim($reason);

        return DB::transaction(function () use ($order, $user, $reason, $ip, $userAgent) {
            /** @var Order $order */
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->status === 'cancelled') {
                throw ValidationException::withMessages(['void' => 'Esta venta ya está anulada.']);
            }
            if ($order->status !== 'completed') {
                throw ValidationException::withMessages(['void' => 'Solo se pueden anular ventas completadas.']);
            }

            $order->load(['items', 'payments']);
            $fiscal = FiscalDocument::where('order_id', $order->id)->where('document_type', 'FACTURA')->first();

            // Una factura ya emitida por una imprenta real solo se corrige con nota de crédito fiscal
            if ($fiscal && in_array($fiscal->status, ['issued', 'contingency'], true) && $fiscal->provider !== 'sandbox') {
                throw ValidationException::withMessages([
                    'void' => 'La venta tiene factura fiscal emitida (N° ' . ($fiscal->invoice_number ?: 's/n') . '). '
                        . 'Debe corregirse con una nota de crédito emitida por la imprenta, función aún no disponible.',
                ]);
            }

            // El reembolso sale de la caja del usuario que anula (o del turno original si sigue abierto)
            $refundable = $order->payments->filter(fn (Payment $p) => (float) $p->amount > 0);
            $refundShiftId = null;
            if ($refundable->isNotEmpty()) {
                $shift = CashShift::activeFor($user);
                if (!$shift && $order->cashShift?->is_active) {
                    $shift = $order->cashShift;
                }
                if (!$shift) {
                    throw ValidationException::withMessages(['void' => 'Abre tu turno de caja para registrar la devolución del cobro.']);
                }
                $refundShiftId = $shift->id;
            }

            $before = ['status' => $order->status, 'payment_status' => $order->payment_status, 'total' => (float) $order->total];

            // Libro: asiento original (si faltara se crea) y nota de crédito que lo contrarresta
            $original = FiscalLedgerService::recordSale($order);

            $affected = $fiscal?->invoice_number ?: ($order->external_invoice_number ?: $order->order_number);

            $note = CreditNote::create([
                'number' => 'NC-' . now()->format('Ymd') . '-' . strtoupper(uniqid()),
                'order_id' => $order->id,
                'user_id' => $user->id,
                'customer_name' => $order->customerParty?->name ?? $order->customer_name,
                'amount' => (float) $order->total,
                'reason' => $reason,
                'status' => 'applied',
                'affected_document_number' => $affected,
            ]);

            foreach ($order->items as $item) {
                CreditNoteItem::create([
                    'credit_note_id' => $note->id,
                    'order_item_id' => $item->id,
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total' => $item->total,
                ]);
            }

            InventoryService::restoreForOrder($order, $user->id);

            foreach ($refundable as $payment) {
                Payment::create([
                    'order_id' => $order->id,
                    'cash_shift_id' => $refundShiftId,
                    'method' => $payment->method,
                    'amount' => -1 * (float) $payment->amount,
                    'reference' => 'Anulación ' . $note->number,
                    'cashea_initial_percentage' => $payment->cashea_initial_percentage,
                    'cashea_financed_amount' => $payment->cashea_financed_amount !== null
                        ? -1 * (float) $payment->cashea_financed_amount
                        : null,
                ]);
            }

            // Un documento fiscal aún sin emitir deja de intentarse
            if ($fiscal && in_array($fiscal->status, ['pending', 'error'], true)) {
                $fiscal->update(['status' => 'cancelled', 'resolved_by' => $user->id]);
            }

            FiscalLedgerService::recordCreditNote($note, $order, $original);

            $order->update([
                'status' => 'cancelled',
                'payment_status' => 'refunded',
                'voided_at' => now(),
                'voided_by' => $user->id,
                'void_reason' => $reason,
            ]);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'sale_voided',
                'entity_type' => 'Order',
                'entity_id' => $order->id,
                'description' => "Anuló la venta {$order->order_number} (nota de crédito {$note->number}): {$reason}",
                'data_before' => $before,
                'data_after' => ['status' => 'cancelled', 'payment_status' => 'refunded', 'credit_note' => $note->number],
                'ip_address' => $ip,
                'user_agent' => $userAgent,
            ]);

            return $note;
        });
    }
}
