<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\Setting;
use Illuminate\Http\Request;

class AccountsPayableDashboardController extends Controller
{
    public function index(Request $request)
    {
        $bcvRate = (float) Setting::get('bcv_rate', 0);

        $query = SupplierInvoice::with('supplier.party', 'payments');

        // Filtros simples
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        } else {
            // Por defecto solo pendientes/parciales
            $query->whereIn('status', ['pendiente', 'parcial']);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('date', '>=', $request->get('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('date', '<=', $request->get('to_date'));
        }

        $invoices = $query->get();

        // Totales generales
        $totalUsd = $invoices->sum('amount_usd');
        $totalPaidUsd = $invoices->sum->paid_usd;
        $totalRemainingUsd = $invoices->sum->remaining_usd;

        // Resumen por proveedor
        $bySupplier = $invoices
            ->groupBy('supplier_id')
            ->map(function ($group) {
                /** @var \Illuminate\Support\Collection $group */
                $first = $group->first();
                $supplier = $first->supplier;
                $party = $supplier?->party;

                return [
                    'supplier' => $supplier,
                    'party' => $party,
                    'invoice_count' => $group->count(),
                    'total_amount_usd' => $group->sum('amount_usd'),
                    'total_paid_usd' => $group->sum->paid_usd,
                    'total_remaining_usd' => $group->sum->remaining_usd,
                ];
            })
            ->values();

        return view('admin.suppliers.dashboard', [
            'bcvRate' => $bcvRate,
            'totalUsd' => $totalUsd,
            'totalPaidUsd' => $totalPaidUsd,
            'totalRemainingUsd' => $totalRemainingUsd,
            'bySupplier' => $bySupplier,
        ]);
    }
}
