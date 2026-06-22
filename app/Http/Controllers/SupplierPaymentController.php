<?php

namespace App\Http\Controllers;

use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;
use App\Models\Setting;
use Illuminate\Http\Request;

class SupplierPaymentController extends Controller
{
    public function store(Request $request, SupplierInvoice $supplierInvoice)
    {
        $data = $this->validateData($request);
        $data['supplier_invoice_id'] = $supplierInvoice->id;

        SupplierPayment::create($data);

        // Actualizar estado básico de la factura
        $supplierInvoice->refresh();
        $remaining = $supplierInvoice->remaining_usd;
        $supplierInvoice->status = $remaining <= 0.01 ? 'pagada' : 'parcial';
        $supplierInvoice->save();

        return redirect()->route('supplier-invoices.show', $supplierInvoice)
            ->with('status', 'Pago registrado correctamente.');
    }

    protected function validateData(Request $request): array
    {
        $bcvRate = (float) Setting::get('bcv_rate', 0);

        $data = $request->validate([
            'payment_date' => 'required|date',
            'amount_usd' => 'nullable|numeric|min:0',
            'amount_bs' => 'nullable|numeric|min:0',
            'bcv_rate_at_payment' => 'nullable|numeric|min:0',
            'method' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $data['bcv_rate_at_payment'] = $data['bcv_rate_at_payment'] ?? $bcvRate;

        if (empty($data['amount_usd']) && empty($data['amount_bs'])) {
            $data['amount_bs'] = 0; // para que pase la validación numérica, aunque idealmente debemos evitar esto
        }

        return $data;
    }
}
