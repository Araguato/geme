<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index()
    {
        $warehouses = Warehouse::orderBy('sort_order')->orderBy('name')->get();
        return view('warehouses.index', compact('warehouses'));
    }

    public function create()
    {
        return view('warehouses.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:50|unique:warehouses,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        Warehouse::create($data);

        return redirect()->route('warehouses.index')->with('success', 'Depósito creado.');
    }

    public function edit(Warehouse $warehouse)
    {
        return view('warehouses.edit', compact('warehouse'));
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $data = $request->validate([
            'code' => 'required|string|max:50|unique:warehouses,code,' . $warehouse->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $warehouse->update($data);

        return redirect()->route('warehouses.index')->with('success', 'Depósito actualizado.');
    }

    public function destroy(Warehouse $warehouse)
    {
        if ($warehouse->products()->exists() || $warehouse->locations()->exists()) {
            return redirect()->route('warehouses.index')->with('error', 'No se puede eliminar: tiene productos o ubicaciones asociadas.');
        }

        $warehouse->delete();
        return redirect()->route('warehouses.index')->with('success', 'Depósito eliminado.');
    }
}
