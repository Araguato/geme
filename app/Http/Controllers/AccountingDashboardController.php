<?php

namespace App\Http\Controllers;

use App\Models\CashShift;
use App\Models\Expense;
use App\Models\Setting;
use App\Models\SupplierInvoice;
use App\Services\SalesReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Panel financiero: pantalla de inicio del rol Contable (también lo ven admin y supervisor).
 */
class AccountingDashboardController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['month' => 'nullable|date_format:Y-m']);

        $month = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->input('month'))->startOfMonth()
            : now()->startOfMonth();
        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();

        $report = SalesReportService::forRange($from, $to);
        $sales = $report->totals();
        $prev = SalesReportService::forRange($from->copy()->subMonthNoOverflow()->startOfMonth(), $from->copy()->subMonthNoOverflow()->endOfMonth())->totals();
        $salesChange = $prev['net'] > 0 ? round(($sales['net'] - $prev['net']) / $prev['net'] * 100, 1) : null;

        // Cobros del mes por forma de pago
        $paymentsByMethod = DB::table('payments as pay')
            ->join('orders as o', 'o.id', '=', 'pay.order_id')
            ->where('o.status', 'completed')
            ->whereBetween('pay.created_at', [$from, $to])
            ->groupBy('pay.method')
            ->selectRaw('pay.method, COUNT(*) as count, SUM(pay.amount) as amount, SUM(COALESCE(pay.cashea_financed_amount, 0)) as financed')
            ->orderByDesc('amount')
            ->get();

        // Compras del mes (facturas de proveedor por fecha de factura)
        $purchases = SupplierInvoice::whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('COUNT(*) as count, SUM(amount_usd) as amount_usd')
            ->first();

        // Cuentas por pagar abiertas (a hoy, no dependen del mes elegido)
        $openInvoices = SupplierInvoice::with('payments')->whereIn('status', ['pendiente', 'parcial'])->get();
        $payables = [
            'count' => $openInvoices->count(),
            'remaining_usd' => $openInvoices->sum->remaining_usd,
            'overdue_count' => $openInvoices->filter(fn ($i) => $i->due_date && Carbon::parse($i->due_date)->lt(today()))->count(),
            'overdue_usd' => $openInvoices->filter(fn ($i) => $i->due_date && Carbon::parse($i->due_date)->lt(today()))->sum->remaining_usd,
        ];
        $dueSoon = $openInvoices
            ->filter(fn ($i) => $i->due_date && Carbon::parse($i->due_date)->lte(today()->addDays(7)))
            ->sortBy('due_date')
            ->take(8);
        $dueSoon->load('supplier.party');

        $financesEnabled = (bool) Setting::get('finances_enabled', 0);
        $expenses = $financesEnabled
            ? (float) Expense::whereBetween('date', [$from->toDateString(), $to->toDateString()])->where('type', 'business')->sum('amount')
            : null;

        // Cierres de caja del mes
        $shifts = CashShift::with(['user:id,name', 'salesLocation:id,name'])
            ->whereNotNull('closed_at')
            ->whereBetween('closed_at', [$from, $to])
            ->withSum('payments as collected', 'amount')
            ->orderByDesc('closed_at')
            ->limit(10)
            ->get();

        return view('accounting.dashboard', [
            'month' => $month,
            'sales' => $sales,
            'salesChange' => $salesChange,
            'daily' => $report->dailyTotals(),
            'topSellers' => $report->rows('seller', 5),
            'topCategories' => $report->rows('category', 5),
            'paymentsByMethod' => $paymentsByMethod,
            'purchases' => $purchases,
            'payables' => $payables,
            'dueSoon' => $dueSoon,
            'financesEnabled' => $financesEnabled,
            'expenses' => $expenses,
            'shifts' => $shifts,
            'bcvRate' => (float) Setting::get('bcv_rate', 0),
            'canEditExpenses' => $request->user()->hasAnyRole(['admin', 'contable']),
        ]);
    }
}
