@extends('layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Nueva ubicación</h1>
    <button class="btn btn-sm btn-outline-secondary" onclick="startLocationFormTour()">
        <i class="bi bi-question-circle"></i> Tour del formulario
    </button>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('locations.store') }}" method="POST" class="mt-3" id="locationForm">
    @csrf
    <div class="mb-3" id="loc-warehouse">
        <label class="form-label">Depósito</label>
        <select name="warehouse_id" class="form-select" required>
            <option value="">Seleccione...</option>
            @foreach($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" {{ old('warehouse_id') == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-md-4" id="loc-code">
            <label class="form-label">Código</label>
            <input type="text" name="code" class="form-control" required value="{{ old('code') }}">
        </div>
        <div class="col-md-8" id="loc-name">
            <label class="form-label">Nombre</label>
            <input type="text" name="name" class="form-control" required value="{{ old('name') }}">
        </div>
    </div>
    <div class="row g-3 mb-3" id="loc-details">
        <div class="col-md-2">
            <label class="form-label">Pasillo</label>
            <input type="text" name="aisle" class="form-control" value="{{ old('aisle') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">Estante</label>
            <input type="text" name="shelf" class="form-control" value="{{ old('shelf') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">Anaquel</label>
            <input type="text" name="rack" class="form-control" value="{{ old('rack') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">Cajón</label>
            <input type="text" name="bin" class="form-control" value="{{ old('bin') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">Vitrina/Sección</label>
            <input type="text" name="section" class="form-control" value="{{ old('section') }}">
        </div>
    </div>
    <div class="mb-3" id="loc-description">
        <label class="form-label">Descripción</label>
        <textarea name="description" class="form-control">{{ old('description') }}</textarea>
    </div>
    <div class="mb-3" id="loc-order">
        <label class="form-label">Orden</label>
        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', 0) }}">
    </div>
    <div class="form-check mb-3" id="loc-active">
        <input type="checkbox" name="is_active" class="form-check-input" id="is_active" checked value="1">
        <label class="form-check-label" for="is_active">Activo</label>
    </div>
    <button type="submit" class="btn btn-primary">Guardar</button>
    <a href="{{ route('locations.index') }}" class="btn btn-secondary">Cancelar</a>
</form>

<script>
    function startLocationFormTour() {
        if (typeof introJs === 'undefined') return;
        introJs()
            .setOptions({
                steps: [
                    { element: '#loc-warehouse', intro: 'Selecciona el depósito al que pertenece esta ubicación. Debes haber creado el depósito primero.' },
                    { element: '#loc-code', intro: 'Código corto y único para identificar la ubicación. Ej: A-3, C-5, VIT-01.' },
                    { element: '#loc-name', intro: 'Nombre descriptivo que verás en los selects de productos y stock. Ej: Pasillo A - Estante 3.' },
                    { element: '#loc-details', intro: 'Completa los niveles de detalle que apliquen: pasillo, estante, anaquel, cajón o vitrina. No es obligatorio llenarlos todos.' },
                    { element: '#loc-description', intro: 'Opcional: notas internas sobre la ubicación.' },
                    { element: '#loc-order', intro: 'Orden numérico para que la ubicación aparezca antes o después en los listados.' },
                    { element: '#loc-active', intro: 'Desactiva una ubicación si ya no la usas. Los productos asignados no se verán afectados, pero no podrás seleccionarla en formularios nuevos.' }
                ],
                nextLabel: 'Siguiente',
                prevLabel: 'Anterior',
                skipLabel: 'Saltar',
                doneLabel: 'Listo',
                showProgress: true,
                showBullets: true,
            })
            .start();
    }
</script>
@endsection
