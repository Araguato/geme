@extends('layout')

@section('content')
<h1 class="mb-3">{{ $mode === 'create' ? 'Nueva conversión de unidad' : 'Editar conversión de unidad' }}</h1>

<form method="POST" action="{{ $mode === 'create' ? route('unit-conversions.store') : route('unit-conversions.update', $conversion) }}">
    @csrf
    @if($mode === 'edit')
        @method('PUT')
    @endif

    <div class="mb-3">
        <label for="from_unit_id" class="form-label">Desde unidad</label>
        <select name="from_unit_id" id="from_unit_id" class="form-select" required>
            <option value="">Seleccione unidad</option>
            @foreach($units as $unit)
                <option value="{{ $unit->id }}" {{ old('from_unit_id', $conversion->from_unit_id) == $unit->id ? 'selected' : '' }}>
                    {{ $unit->category ? '['.$unit->category.'] ' : '' }}{{ $unit->name }} ({{ $unit->code }})
                </option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label for="to_unit_id" class="form-label">Hacia unidad</label>
        <select name="to_unit_id" id="to_unit_id" class="form-select" required>
            <option value="">Seleccione unidad</option>
            @foreach($units as $unit)
                <option value="{{ $unit->id }}" {{ old('to_unit_id', $conversion->to_unit_id) == $unit->id ? 'selected' : '' }}>
                    {{ $unit->category ? '['.$unit->category.'] ' : '' }}{{ $unit->name }} ({{ $unit->code }})
                </option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label for="factor" class="form-label">Factor (1 "desde" = factor * "hacia")</label>
        <input type="number" name="factor" id="factor" class="form-control" step="any" min="0.00000001" value="{{ old('factor', $conversion->factor) }}" required>
    </div>

    <div class="form-check mb-3">
        <input type="checkbox" name="is_active" id="is_active" value="1" class="form-check-input" {{ old('is_active', $conversion->is_active) ? 'checked' : '' }}>
        <label for="is_active" class="form-check-label">Activa</label>
    </div>

    <button type="submit" class="btn btn-primary">Guardar</button>
    <a href="{{ route('unit-conversions.index') }}" class="btn btn-secondary">Cancelar</a>
</form>
@endsection
