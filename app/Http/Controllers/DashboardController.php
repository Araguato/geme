<?php

namespace App\Http\Controllers;

use App\Models\CashShift;
use App\Models\Order;
use App\Models\Product;
use App\Models\SupplierInvoice;
use App\Services\FiscalLedgerService;
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
            ->whereRaw('(stock_quantity IS NOT NULL AND stock_quantity <= reorder_point)')
            ->orWhere(function ($q) {
                $q->where('is_active', true)
                    ->whereColumn('stock_quantity', '<=', 'reorder_point');
            })
            ->count();

        $pendingInvoices = SupplierInvoice::where('status', 'pending')->count();
        $pendingInvoiceAmount = SupplierInvoice::where('status', 'pending')->sum('total_amount');

        $activeShift = CashShift::where('is_active', true)->latest()->first();

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
            'chartValues'
        ));
    }
}
