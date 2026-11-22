<?php

namespace App\Http\Controllers;

use App\Models\CashShift;
use App\Models\Order;
use App\Models\Product;
use App\Models\SupplierInvoice;
use App\Services\FiscalLedgerService;
use App\Services\SalesReportService;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        $salesToday = Order::whereDate('created_at', $today)
            ->where('status', 'completed')
            ->sum('total');

        $salesMonth = Order::whereBetween('created_at', [$startOfMonth, $endOfMonth . ' 23:59:59'])
            ->where('status', 'completed')
            ->sum('total');

        $ordersToday = Order::whereDate('created_at', $today)
            ->where('status', 'completed')
            ->count();

        $ordersMonth = Order::whereBetween('created_at', [$startOfMonth, $endOfMonth . ' 23:59:59'])
            ->where('status', 'completed')
            ->count();

        $productsCount = Product::where('is_active', true)->count();
        $lowStock = Product::where('is_active', true)
            ->where('reorder_point', '>', 0)
            ->whereColumn('stock_quantity', '<=', 'reorder_point')
            ->count();

        // Estados reales de las facturas de proveedor: pendiente / parcial / pagada
        $openInvoices = SupplierInvoice::with('payments')->whereIn('status', ['pendiente', 'parcial'])->get();
        $pendingInvoices = $openInvoices->count();
        $pendingInvoiceAmount = $openInvoices->sum->remaining_usd;

        // Estadísticas avanzadas (admin / supervisor): comparación con el mes anterior
        // hasta el mismo día, margen y ranking por vendedor, categoría y producto.
        $monthTotals = null;
        $salesChange = null;
        $topSellers = $topCategories = $topProducts = collect();
        if (auth()->user()->hasAnyRole(['admin', 'supervisor'])) {
            $monthReport = SalesReportService::forRange(now()->startOfMonth(), now());
            $monthTotals = $monthReport->totals();
            $prevTotals = SalesReportService::forRange(
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()
            )->totals();
            $salesChange = $prevTotals['net'] > 0
                ? round(($monthTotals['net'] - $prevTotals['net']) / $prevTotals['net'] * 100, 1)
                : null;
            $topSellers = $monthReport->rows('seller', 5);
            $topCategories = $monthReport->rows('category', 5);
            $topProducts = $monthReport->rows('product', 5);
        }

        $activeShift = CashShift::activeFor(auth()->user());

        $recentSales = Order::where('status', 'completed')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $lowStockProducts = Product::where('is_active', true)
            ->whereColumn('stock_quantity', '<=', 'reorder_point')
            ->where('reorder_point', '>', 0)
            ->orderBy('stock_quantity')
            ->limit(5)
            ->get();

        $monthlySales = Order::selectRaw('DATE(created_at) as date, SUM(total) as total')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth . ' 23:59:59'])
            ->where('status', 'completed')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('total', 'date')
            ->toArray();

        $chartLabels = array_keys($monthlySales);
        $chartValues = array_values($monthlySales);

        return view('dashboard', compact(
            'salesToday',
            'salesMonth',
            'ordersToday',
            'ordersMonth',
            'productsCount',
            'lowStock',
            'pendingInvoices',
            'pendingInvoiceAmount',
            'activeShift',
            'recentSales',
            'lowStockProducts',
            'chartLabels',
            'chartValues',
            'monthTotals',
            'salesChange',
            'topSellers',
            'topCategories',
            'topProducts'
        ));
    }
}
