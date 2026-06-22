@extends('layout')

@section('content')
<h1>{{ $mode === 'create' ? 'Nueva unidad' : 'Editar unidad' }}</h1>

<form method="POST" action="{{ $mode === 'create' ? route('units.store') : route('units.update', $unit) }}">
    @csrf
    @if($mode === 'edit')
        @method('PUT')
    @endif

    <div class="mb-3">
        <label for="code" class="form-label">Código</label>
        <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $unit->code) }}" required>
        @error('code')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="name" class="form-label">Nombre</label>
        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $unit->name) }}" required>
        @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="category" class="form-label">Categoría (opcional)</label>
        <input type="text" class="form-control @error('category') is-invalid @enderror" id="category" name="category" value="{{ old('category', $unit->category) }}" placeholder="peso, volumen, pieza, etc.">
        @error('category')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" value="1" id="is_active" name="is_active" {{ old('is_active', $unit->is_active) ? 'checked' : '' }}>
        <label class="form-check-label" for="is_active">
            Activa
        </label>
    </div>

    <button type="submit" class="btn btn-primary">Guardar</button>
    <a href="{{ route('units.index') }}" class="btn btn-secondary">Cancelar</a>
</form>
@endsection
