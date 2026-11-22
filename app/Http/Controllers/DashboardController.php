<?php

namespace App\Http\Controllers;

use App\Models\CashShift;
use App\Models\Order;
use App\Models\Product;
use App\Models\SupplierInvoice;
use App\Services\SalesReportService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Panel principal. Admin y supervisor ven primero el análisis del negocio y abajo los
 * accesos; el resto del personal ve sus accesos y el estado de su caja.
 */
class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $isAnalyst = $user->hasAnyRole(['admin', 'supervisor']);
        $activeShift = CashShift::activeFor($user);

        $data = compact('isAnalyst', 'activeShift');

        if ($isAnalyst) {
            $data += $this->analytics();
        }

        return view('dashboard', $data);
    }

    protected function analytics(): array
    {
        $now = now();

        // Hoy frente a ayer hasta la misma hora
        $today = SalesReportService::forRange($now->copy()->startOfDay(), $now)->totals();
        $yesterday = $this->ordersBetween($now->copy()->subDay()->startOfDay(), $now->copy()->subDay());

        // Mes en curso frente al mes anterior hasta el mismo día
        $monthReport = SalesReportService::forRange($now->copy()->startOfMonth(), $now);
        $month = $monthReport->totals();
        $prevMonth = SalesReportService::forRange(
            $now->copy()->subMonthNoOverflow()->startOfMonth(),
            $now->copy()->subMonthNoOverflow()
        )->totals();

        $change = fn ($cur, $prev) => $prev > 0 ? round(($cur - $prev) / $prev * 100, 1) : null;

        // Ventas por día: mes actual frente al anterior, alineados por día del mes
        $daysShown = $now->day;
        $currentDaily = $this->dailyTotals($now->copy()->startOfMonth(), $now);
        $prevStart = $now->copy()->subMonthNoOverflow()->startOfMonth();
        $prevDaily = $this->dailyTotals($prevStart, $prevStart->copy()->endOfMonth());
        $daily = ['labels' => [], 'current' => [], 'previous' => []];
        for ($d = 1; $d <= $daysShown; $d++) {
            $daily['labels'][] = $d;
            $daily['current'][] = $currentDaily[$d] ?? 0;
            $daily['previous'][] = $prevDaily[$d] ?? 0;
        }

        // Ventas por hora del día (mes en curso): horas pico
        $hourly = array_fill(0, 24, 0.0);
        foreach ($this->hourlyTotals($now->copy()->startOfMonth(), $now) as $hour => $total) {
            $hourly[(int) $hour] = round((float) $total, 2);
        }
        $firstHour = 0;
        $lastHour = 23;
        $nonZero = array_keys(array_filter($hourly));
        if ($nonZero) {
            $firstHour = max(0, min($nonZero) - 1);
            $lastHour = min(23, max($nonZero) + 1);
        }
        $hourlyChart = [
            'labels' => array_map(fn ($h) => sprintf('%02d h', $h), range($firstHour, $lastHour)),
            'values' => array_slice($hourly, $firstHour, $lastHour - $firstHour + 1),
        ];

        $paymentMethods = DB::table('payments as pay')
            ->join('orders as o', 'o.id', '=', 'pay.order_id')
            ->where('o.status', 'completed')
            ->whereBetween('o.created_at', [$now->copy()->startOfMonth(), $now])
            ->groupBy('pay.method')
            ->selectRaw('pay.method, SUM(pay.amount) as amount')
            ->orderByDesc('amount')
            ->pluck('amount', 'method')
            ->map(fn ($v) => round((float) $v, 2))
            ->toArray();

        // Estados reales de las facturas de proveedor: pendiente / parcial / pagada
        $openInvoices = SupplierInvoice::with('payments')->whereIn('status', ['pendiente', 'parcial'])->get();
        $payables = [
            'count' => $openInvoices->count(),
            'amount' => $openInvoices->sum->remaining_usd,
            'overdue' => $openInvoices->filter(fn ($i) => $i->due_date && Carbon::parse($i->due_date)->lt(today()))->count(),
        ];

        $lowStockQuery = Product::where('is_active', true)
            ->where('reorder_point', '>', 0)
            ->whereColumn('stock_quantity', '<=', 'reorder_point');

        return [
            'today' => $today,
            'todayChange' => $change($today['net'], $yesterday),
            'month' => $month,
            'monthChange' => $change($month['net'], $prevMonth['net']),
            'avgTicket' => $month['tickets'] ? $month['total'] / $month['tickets'] : 0,
            'prevAvgTicket' => $prevMonth['tickets'] ? $prevMonth['total'] / $prevMonth['tickets'] : 0,
            'daily' => $daily,
            'hourly' => $hourlyChart,
            'paymentMethods' => $paymentMethods,
            'topSellers' => $monthReport->rows('seller', 5),
            'topCategories' => $monthReport->rows('category', 5),
            'topProducts' => $monthReport->rows('product', 5),
            'payables' => $payables,
            'lowStockCount' => (clone $lowStockQuery)->count(),
            'lowStockProducts' => $lowStockQuery->orderByRaw('stock_quantity - reorder_point')->limit(6)
                ->get(['id', 'name', 'stock_quantity', 'reorder_point', 'default_unit']),
            'recentSales' => Order::with('user:id,name')->where('status', 'completed')
                ->orderByDesc('created_at')->limit(6)->get(),
        ];
    }

    /** Venta neta (sin IVA) de órdenes completadas en el rango. */
    protected function ordersBetween(Carbon $from, Carbon $to): float
    {
        return (float) Order::where('status', 'completed')->whereBetween('created_at', [$from, $to])->sum('subtotal');
    }

    /** [día del mes => total con IVA] */
    protected function dailyTotals(Carbon $from, Carbon $to): array
    {
        return Order::where('status', 'completed')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as d, SUM(total) as total')
            ->groupBy('d')
            ->get()
            ->mapWithKeys(fn ($r) => [(int) substr($r->d, 8, 2) => round((float) $r->total, 2)])
            ->toArray();
    }

    /** [hora => total con IVA] */
    protected function hourlyTotals(Carbon $from, Carbon $to): array
    {
        $hourExpr = DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%H', created_at) AS INTEGER)"
            : 'HOUR(created_at)';

        return Order::where('status', 'completed')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("$hourExpr as h, SUM(total) as total")
            ->groupBy('h')
            ->pluck('total', 'h')
            ->toArray();
    }
}
