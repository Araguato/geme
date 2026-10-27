<?php

namespace App\Http\Controllers;

use App\Models\SalesLocation;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class SalesLocationController extends Controller
{
    public function index()
    {
        $locations = SalesLocation::with('warehouse')->orderBy('name')->get();
        return view('admin.sales-locations.index', compact('locations'));
    }

    public function create()
    {
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return view('admin.sales-locations.form', compact('warehouses'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'is_active' => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        SalesLocation::create($data);

        return redirect()->route('sales-locations.index')->with('success', 'Ubicación de venta creada.');
    }

    public function edit(SalesLocation $salesLocation)
    {
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return view('admin.sales-locations.form', ['location' => $salesLocation, 'warehouses' => $warehouses]);
    }

    public function update(Request $request, SalesLocation $salesLocation)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'is_active' => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $salesLocation->update($data);

        return redirect()->route('sales-locations.index')->with('success', 'Ubicación de venta actualizada.');
    }

    public function destroy(SalesLocation $salesLocation)
    {
        $salesLocation->delete();
        return redirect()->route('sales-locations.index')->with('success', 'Ubicación de venta eliminada.');
    }
}
