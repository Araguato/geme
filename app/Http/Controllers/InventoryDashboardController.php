<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\SupplierInvoice;

/**
 * Panel de almacén: pantalla de inicio del almacenista. Sin precios ni costos.
 */
class InventoryDashboardController extends Controller
{
    public function index()
    {
        $tracked = Product::where('is_active', true)->where('is_stock_tracked', true);

        $lowStockQuery = (clone $tracked)
            ->where('reorder_point', '>', 0)
            ->whereColumn('stock_quantity', '<=', 'reorder_point');

        $stats = [
            'tracked' => (clone $tracked)->count(),
            'low' => (clone $lowStockQuery)->count(),
            'out' => (clone $tracked)->where('stock_quantity', '<=', 0)->count(),
            'movements_today' => StockMovement::whereDate('movement_date', today())->count(),
        ];

        $lowStock = $lowStockQuery->with('category:id,name')
            ->orderByRaw('stock_quantity - reorder_point')
            ->limit(15)
            ->get(['id', 'name', 'sku', 'category_id', 'stock_quantity', 'reorder_point', 'default_unit']);

        $movements = StockMovement::with(['product:id,name', 'warehouse:id,name', 'user:id,name'])
            ->orderByDesc('movement_date')
            ->orderByDesc('id')
            ->limit(12)
            ->get(['id', 'product_id', 'warehouse_id', 'movement_date', 'type', 'reason', 'quantity', 'unit', 'performed_by']);

        // Compras de los últimos días: mercancía que debería haber entrado
        $recentPurchases = SupplierInvoice::with('supplier.party')
            ->where('date', '>=', today()->subDays(7)->toDateString())
            ->orderByDesc('date')
            ->limit(8)
            ->get(['id', 'supplier_id', 'invoice_number', 'date']);

        return view('inventory.dashboard', compact('stats', 'lowStock', 'movements', 'recentPurchases'));
    }
}
