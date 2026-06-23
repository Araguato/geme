<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class StockController extends Controller
{
    public function index()
    {
        $stockItems = StockItem::with('product', 'warehouse', 'location')
            ->get()
            ->sortBy(fn (StockItem $item) => $item->product?->name ?? '');

        $totalValue = $stockItems->sum(function (StockItem $item) {
            return (float) $item->quantity * (float) $item->average_cost;
        });

        return view('admin.stock.index', compact('stockItems', 'totalValue'));
    }

    public function movements(Product $product)
    {
        $movements = StockMovement::with('warehouse', 'toWarehouse', 'location')
            ->where('product_id', $product->id)
            ->orderBy('movement_date')
            ->orderBy('id')
            ->get();

        return view('admin.stock.movements', compact('product', 'movements'));
    }

    public function adjustForm(Product $product = null)
    {
        $products = Product::with('warehouse', 'location')->orderBy('name')->get();
        $units = Unit::where('is_active', true)->orderBy('category')->orderBy('name')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $locations = Location::where('is_active', true)->with('warehouse')->orderBy('name')->get();

        // Determinar producto seleccionado (parámetro explícito o valor previo del formulario)
        $selectedProduct = $product;
        if (!$selectedProduct && old('product_id')) {
            $selectedProduct = Product::find(old('product_id'));
        }

        $selectedStockItem = null;
        if ($selectedProduct) {
            $selectedStockItem = StockItem::where('product_id', $selectedProduct->id)
                ->where('warehouse_id', old('warehouse_id', $selectedProduct->warehouse_id))
                ->where('location_id', old('location_id', $selectedProduct->location_id))
                ->first();
        }

        return view('admin.stock.adjust', [
            'products' => $products,
            'units' => $units,
            'warehouses' => $warehouses,
            'locations' => $locations,
            'selectedProduct' => $selectedProduct,
            'selectedStockItem' => $selectedStockItem,
        ]);
    }

    public function adjust(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:in,out,adjustment,transfer',
            'reason' => 'required|string|max:50',
            'quantity' => 'required|numeric|min:0.0001',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'to_warehouse_id' => 'nullable|exists:warehouses,id',
            'to_location_id' => 'nullable|exists:locations,id',
            'location_id' => 'nullable|exists:locations,id',
            'aisle' => 'nullable|string|max:20',
            'shelf' => 'nullable|string|max:20',
            'rack' => 'nullable|string|max:20',
            'bin' => 'nullable|string|max:20',
            'section' => 'nullable|string|max:20',
            'unit' => 'nullable|string|max:20', // texto legado opcional
            'unit_id' => 'nullable|exists:units,id',
            'unit_cost' => 'nullable|numeric|min:0',
        ]);

        $product = Product::findOrFail($data['product_id']);
        $userId = $request->user()?->id;

        DB::transaction(function () use ($data, $product, $userId) {
            $textUnit = $data['unit'] ?? null;
            $qty = (float) $data['quantity'];
            $type = $data['type'];
            $unitCost = $data['unit_cost'] ?? null;
            $warehouseId = $data['warehouse_id'] ?? null;
            $locationId = $data['location_id'] ?? null;
            $toWarehouseId = $data['to_warehouse_id'] ?? null;
            $toLocationId = $data['to_location_id'] ?? null;
            $locationDetails = [
                'aisle' => $data['aisle'] ?? null,
                'shelf' => $data['shelf'] ?? null,
                'rack' => $data['rack'] ?? null,
                'bin' => $data['bin'] ?? null,
                'section' => $data['section'] ?? null,
            ];

            if ($type === 'transfer') {
                if (!$warehouseId || !$toWarehouseId) {
                    throw new \InvalidArgumentException('Para transferencias se requiere depósito origen y destino.');
                }
                // Salida del origen
                $this->processStockMovement($product, $warehouseId, $locationId, $qty, 'out', $unitCost, $data['reason'], $data['unit_id'] ?? null, $textUnit, $userId, $toWarehouseId, $locationDetails);
                // Entrada al destino (usa el mismo costo promedio del origen)
                $originItem = StockItem::where('product_id', $product->id)
                    ->where('warehouse_id', $warehouseId)
                    ->where('location_id', $locationId)
                    ->first();
                $transferCost = $originItem ? (float) $originItem->average_cost : 0;
                $this->processStockMovement($product, $toWarehouseId, $toLocationId, $qty, 'in', $transferCost, $data['reason'], $data['unit_id'] ?? null, $textUnit, $userId, null, []);
            } else {
                $this->processStockMovement($product, $warehouseId, $locationId, $qty, $type, $unitCost, $data['reason'], $data['unit_id'] ?? null, $textUnit, $userId, null, $locationDetails);
            }
        });

        return redirect()
            ->route('stock.index')
            ->with('status', 'Ajuste de inventario registrado correctamente.');
    }

    private function processStockMovement(Product $product, ?int $warehouseId, ?int $locationId, float $qty, string $type, ?float $unitCost, string $reason, ?int $unitId, ?string $textUnit, ?int $userId, ?int $toWarehouseId, array $locationDetails): void
    {
        $stockItem = StockItem::firstOrCreate(
            ['product_id' => $product->id, 'warehouse_id' => $warehouseId, 'location_id' => $locationId],
            [
                'unit_id' => $unitId ?? $product->stock_unit_id,
                'quantity' => 0,
                'unit' => $textUnit ?: ($product->default_unit ?: null),
                'average_cost' => 0,
                'min_quantity' => 0,
            ]
        );

        $oldQty = (float) $stockItem->quantity;
        $oldAvg = (float) $stockItem->average_cost;

        $direction = $type === 'out' ? -1 : 1;
        $effectiveQty = $qty * $direction;

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
                $newQty = 0;
            }
            $newAvg = $oldAvg;
        }

        $stockItem->quantity = $newQty;
        $stockItem->average_cost = $newAvg;
        $stockItem->unit_id = $unitId ?? $stockItem->unit_id ?? $product->stock_unit_id;
        $stockItem->unit = ($textUnit ?? null) ?: ($stockItem->unit ?: $product->default_unit ?: null);
        $stockItem->save();

        $totalCost = $qty * $unitCost * $direction;

        StockMovement::create(array_merge([
            'product_id' => $product->id,
            'warehouse_id' => $warehouseId,
            'to_warehouse_id' => $toWarehouseId,
            'location_id' => $locationId,
            'unit_id' => $stockItem->unit_id,
            'movement_date' => now(),
            'type' => $type,
            'reason' => $reason,
            'quantity' => $effectiveQty,
            'unit' => $stockItem->unit,
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
            'running_quantity' => $newQty,
            'running_average_cost' => $newAvg,
            'reference_type' => 'manual_adjustment',
            'reference_id' => null,
            'performed_by' => $userId,
        ], $locationDetails));
    }
}
