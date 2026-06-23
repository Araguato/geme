<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index()
    {
        $locations = Location::with('warehouse')->orderBy('warehouse_id')->orderBy('sort_order')->orderBy('name')->get();
        return view('locations.index', compact('locations'));
    }

    public function create()
    {
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        return view('locations.create', compact('warehouses'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'code' => 'required|string|max:50|unique:locations,code',
            'name' => 'required|string|max:255',
            'aisle' => 'nullable|string|max:20',
            'shelf' => 'nullable|string|max:20',
            'rack' => 'nullable|string|max:20',
            'bin' => 'nullable|string|max:20',
            'section' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        Location::create($data);

        return redirect()->route('locations.index')->with('success', 'Ubicación creada.');
    }

    public function edit(Location $location)
    {
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        return view('locations.edit', compact('location', 'warehouses'));
    }

    public function update(Request $request, Location $location)
    {
        $data = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'code' => 'required|string|max:50|unique:locations,code,' . $location->id,
            'name' => 'required|string|max:255',
            'aisle' => 'nullable|string|max:20',
            'shelf' => 'nullable|string|max:20',
            'rack' => 'nullable|string|max:20',
            'bin' => 'nullable|string|max:20',
            'section' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $location->update($data);

        return redirect()->route('locations.index')->with('success', 'Ubicación actualizada.');
    }

    public function destroy(Location $location)
    {
        if ($location->products()->exists() || $location->stockItems()->exists()) {
            return redirect()->route('locations.index')->with('error', 'No se puede eliminar: tiene productos o stock asociados.');
        }

        $location->delete();
        return redirect()->route('locations.index')->with('success', 'Ubicación eliminada.');
    }
}
