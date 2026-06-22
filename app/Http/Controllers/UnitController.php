<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index()
    {
        $units = Unit::orderBy('category')->orderBy('name')->get();
        return view('units.index', compact('units'));
    }

    public function create()
    {
        return view('units.form', [
            'unit' => new Unit(['is_active' => true]),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        Unit::create($data);

        return redirect()->route('units.index');
    }

    public function edit(Unit $unit)
    {
        return view('units.form', [
            'unit' => $unit,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Unit $unit)
    {
        $data = $this->validateData($request, $unit->id);
        $unit->update($data);

        return redirect()->route('units.index');
    }

    public function destroy(Unit $unit)
    {
        $unit->delete();
        return redirect()->route('units.index');
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        $uniqueRule = 'unique:units,code';
        if ($ignoreId) {
            $uniqueRule .= ',' . $ignoreId;
        }

        return $request->validate([
            'code' => ['required', 'string', 'max:50', $uniqueRule],
            'name' => ['required', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ]) + [
            'is_active' => $request->boolean('is_active', true),
        ];
    }
}
