<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class StockController extends Controller
{
    public function index()
    {
        $stockItems = StockItem::with('product')
            ->get()
            ->sortBy(fn (StockItem $item) => $item->product?->name ?? '');

        $totalValue = $stockItems->sum(function (StockItem $item) {
            return (float) $item->quantity * (float) $item->average_cost;
        });

        return view('admin.stock.index', compact('stockItems', 'totalValue'));
    }

    public function movements(Product $product)
    {
        $movements = StockMovement::where('product_id', $product->id)
            ->orderBy('movement_date')
            ->orderBy('id')
            ->get();

        return view('admin.stock.movements', compact('product', 'movements'));
    }

    public function adjustForm(Product $product = null)
    {
        $products = Product::orderBy('name')->get();
        $units = Unit::where('is_active', true)->orderBy('category')->orderBy('name')->get();

        // Determinar producto seleccionado (parámetro explícito o valor previo del formulario)
        $selectedProduct = $product;
        if (!$selectedProduct && old('product_id')) {
            $selectedProduct = Product::find(old('product_id'));
        }

        $selectedStockItem = null;
        if ($selectedProduct) {
            $selectedStockItem = StockItem::where('product_id', $selectedProduct->id)->first();
        }

        return view('admin.stock.adjust', [
            'products' => $products,
            'units' => $units,
            'selectedProduct' => $selectedProduct,
            'selectedStockItem' => $selectedStockItem,
        ]);
    }

    public function adjust(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:in,out,adjustment',
            'reason' => 'required|string|max:50',
            'quantity' => 'required|numeric|min:0.0001',
            'unit' => 'nullable|string|max:20', // texto legado opcional
            'unit_id' => 'nullable|exists:units,id',
            'unit_cost' => 'nullable|numeric|min:0',
        ]);

        $product = Product::findOrFail($data['product_id']);
        $userId = $request->user()?->id;

        DB::transaction(function () use ($data, $product, $userId) {
            $textUnit = $data['unit'] ?? null;

            $stockItem = StockItem::firstOrCreate(
                ['product_id' => $product->id],
                [
                    'unit_id' => $data['unit_id'] ?? $product->stock_unit_id,
                    'quantity' => 0,
                    'unit' => $textUnit ?: ($product->default_unit ?: null),
                    'average_cost' => 0,
                    'min_quantity' => 0,
                ]
            );

            $oldQty = (float) $stockItem->quantity;
            $oldAvg = (float) $stockItem->average_cost;

            $qty = (float) $data['quantity'];
            $type = $data['type'];

            // Determinar signo efectivo
            $direction = $type === 'out' ? -1 : 1;
            $effectiveQty = $qty * $direction;

            // Costo unitario
            $unitCost = $data['unit_cost'] ?? null;

            if ($type === 'in') {
                $unitCost = $unitCost ?? $oldAvg;
                $newQty = $oldQty + $qty;
                if ($newQty <= 0) {
                    $newAvg = 0;
                } else {
                    $newAvg = ($oldQty * $oldAvg + $qty * $unitCost) / $newQty;
                }
            } else {
                // out o adjustment se valora al costo promedio actual
                $unitCost = $unitCost ?? $oldAvg;
                $newQty = $oldQty + $effectiveQty;
                if ($newQty < 0) {
                    $newQty = 0; // evitar negativos duros; más adelante se puede endurecer
                }
                $newAvg = $oldAvg;
            }

            $stockItem->quantity = $newQty;
            $stockItem->average_cost = $newAvg;
            $stockItem->unit_id = $data['unit_id'] ?? $stockItem->unit_id ?? $product->stock_unit_id;
            $stockItem->unit = ($data['unit'] ?? null) ?: ($stockItem->unit ?: $product->default_unit ?: null);
            $stockItem->save();

            $totalCost = $qty * $unitCost * $direction;

            StockMovement::create([
                'product_id' => $product->id,
                'unit_id' => $stockItem->unit_id,
                'movement_date' => now(),
                'type' => $type,
                'reason' => $data['reason'],
                'quantity' => $effectiveQty,
                'unit' => $stockItem->unit,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'running_quantity' => $newQty,
                'running_average_cost' => $newAvg,
                'reference_type' => 'manual_adjustment',
                'reference_id' => null,
                'performed_by' => $userId,
            ]);
        });

        return redirect()
            ->route('stock.index')
            ->with('status', 'Ajuste de inventario registrado correctamente.');
    }
}
