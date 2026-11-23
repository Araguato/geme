<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderVoidService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OrderVoidController extends Controller
{
    public function store(Request $request, Order $order)
    {
        $data = $request->validate([
            'reason' => 'required|string|min:5|max:255',
        ], [
            'reason.required' => 'Indica el motivo de la anulación.',
            'reason.min' => 'El motivo debe tener al menos 5 caracteres.',
        ]);

        try {
            $note = OrderVoidService::void($order, $request->user(), $data['reason'], $request->ip(), $request->userAgent());
        } catch (ValidationException $e) {
            return redirect()->route('pos.ticket', $order)->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()->route('pos.ticket', $order)
            ->with('success', $note
                ? "Venta anulada. Nota de crédito {$note->number} registrada."
                : 'Nota de entrega anulada. Se devolvió el inventario y el cobro.');
    }
}
