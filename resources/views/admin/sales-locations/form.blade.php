@extends('layout')

@section('content')
    <h1>{{ isset($location) ? 'Editar ubicación de venta' : 'Nueva ubicación de venta' }}</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ isset($location) ? route('sales-locations.update', $location) : route('sales-locations.store') }}" class="mt-3">
        @csrf
        @if(isset($location))
            @method('PUT')
        @endif

        <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $location->name ?? '') }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Código (opcional)</label>
            <input type="text" name="code" class="form-control" value="{{ old('code', $location->code ?? '') }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Dirección</label>
            <textarea name="address" class="form-control" rows="2">{{ old('address', $location->address ?? '') }}</textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Teléfono</label>
            <input type="text" name="phone" class="form-control" value="{{ old('phone', $location->phone ?? '') }}">
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" {{ old('is_active', $location->is_active ?? true) ? 'checked' : '' }}>
            <label for="is_active" class="form-check-label">Activo</label>
        </div>

        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="{{ route('sales-locations.index') }}" class="btn btn-secondary">Cancelar</a>
    </form>
@endsection
