<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\UnitConversion;
use Illuminate\Http\Request;

class UnitConversionAdminController extends Controller
{
    public function index()
    {
        $conversions = UnitConversion::with(['fromUnit', 'toUnit'])
            ->orderBy('from_unit_id')
            ->orderBy('to_unit_id')
            ->get();

        return view('admin.unit_conversions.index', compact('conversions'));
    }

    public function create()
    {
        $units = Unit::where('is_active', true)->orderBy('category')->orderBy('name')->get();

        return view('admin.unit_conversions.form', [
            'conversion' => new UnitConversion(['is_active' => true]),
            'units' => $units,
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        UnitConversion::create($data);

        return redirect()->route('unit-conversions.index');
    }

    public function edit(UnitConversion $unit_conversion)
    {
        $units = Unit::where('is_active', true)->orderBy('category')->orderBy('name')->get();

        return view('admin.unit_conversions.form', [
            'conversion' => $unit_conversion,
            'units' => $units,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, UnitConversion $unit_conversion)
    {
        $data = $this->validateData($request, $unit_conversion->id);
        $unit_conversion->update($data);

        return redirect()->route('unit-conversions.index');
    }

    public function destroy(UnitConversion $unit_conversion)
    {
        $unit_conversion->delete();

        return redirect()->route('unit-conversions.index');
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        $fromId = $request->input('from_unit_id');
        $toId = $request->input('to_unit_id');

        $request->merge([
            'is_active' => $request->boolean('is_active', true),
        ]);

        $rules = [
            'from_unit_id' => ['required', 'exists:units,id'],
            'to_unit_id' => ['required', 'exists:units,id', 'different:from_unit_id'],
            'factor' => ['required', 'numeric', 'gt:0'],
            'is_active' => ['nullable', 'boolean'],
        ];

        $messages = [
            'from_unit_id.required' => 'La unidad de origen es obligatoria.',
            'to_unit_id.required' => 'La unidad de destino es obligatoria.',
            'to_unit_id.different' => 'Las unidades de origen y destino deben ser diferentes.',
            'factor.required' => 'El factor de conversión es obligatorio.',
            'factor.gt' => 'El factor de conversión debe ser mayor que cero.',
        ];

        $data = $request->validate($rules, $messages);

        // Validación manual de unicidad (from_unit_id, to_unit_id)
        $query = UnitConversion::where('from_unit_id', $fromId)
            ->where('to_unit_id', $toId);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            abort(422, 'Ya existe una conversión entre esas unidades.');
        }

        return $data;
    }
}
