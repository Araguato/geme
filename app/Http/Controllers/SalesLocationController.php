<?php

namespace App\Http\Controllers;

use App\Models\SalesLocation;
use Illuminate\Http\Request;

class SalesLocationController extends Controller
{
    public function index()
    {
        $locations = SalesLocation::orderBy('name')->get();
        return view('admin.sales-locations.index', compact('locations'));
    }

    public function create()
    {
        return view('admin.sales-locations.form');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        SalesLocation::create($data);

        return redirect()->route('sales-locations.index')->with('success', 'Ubicación de venta creada.');
    }

    public function edit(SalesLocation $salesLocation)
    {
        return view('admin.sales-locations.form', ['location' => $salesLocation]);
    }

    public function update(Request $request, SalesLocation $salesLocation)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
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
