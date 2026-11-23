<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Order;
use App\Services\ExchangeRateService;
use App\Services\Fiscal\FiscalEmissionService;
use App\Services\FiscalLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryNoteController extends Controller
{
    /** Notas de entrega pendientes de facturar. */
    public function index()
    {
        $notes = Order::where('is_delivery_note', true)
            ->where('status', 'completed')
            ->with(['user:id,name', 'customerParty:id,name'])
            ->orderBy('created_at')
            ->paginate(30);

        $invoiced = Order::whereNotNull('delivery_note_number')
            ->whereNotNull('invoiced_at')
            ->with('invoicer:id,name')
            ->latest('invoiced_at')
            ->limit(15)
            ->get();

        return view('delivery_notes.index', compact('notes', 'invoiced'));
    }

    /**
     * Convierte la nota de entrega en venta facturada: desde este momento entra al libro de
     * ventas (con la fecha de facturación) y a la cola de emisión del medio fiscal.
     */
    public function invoice(Request $request, Order $order)
    {
        $result = DB::transaction(function () use ($order, $request) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (!$locked->isPendingDeliveryNote()) {
                return [null, 'Esta nota de entrega ya fue facturada o anulada.'];
            }

            $before = [
                'exchange_rate' => $locked->exchange_rate,
                'delivery_note_number' => $locked->delivery_note_number,
            ];

            // La factura lleva la tasa BCV del día en que se emite, no la de la entrega
            $rate = ExchangeRateService::getRateForDate(now()) ?: null;

            $locked->update([
                'is_delivery_note' => false,
                'invoiced_at' => now(),
                'invoiced_by' => $request->user()->id,
                'exchange_rate' => $rate ?? $locked->exchange_rate,
            ]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'delivery_note_invoiced',
                'entity_type' => 'Order',
                'entity_id' => $locked->id,
                'description' => "Facturó la nota de entrega {$locked->delivery_note_number} (venta {$locked->order_number})",
                'data_before' => $before,
                'data_after' => ['exchange_rate' => $locked->exchange_rate, 'invoiced_at' => (string) $locked->invoiced_at],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return [$locked, null];
        });

        [$invoiced, $error] = $result;
        if (!$invoiced) {
            return back()->with('error', $error);
        }

        FiscalLedgerService::recordSale($invoiced);
        FiscalEmissionService::enqueue($invoiced); // no hace nada si no hay medio fiscal configurado

        return back()->with('success', "Nota de entrega {$invoiced->delivery_note_number} facturada.");
    }
}
