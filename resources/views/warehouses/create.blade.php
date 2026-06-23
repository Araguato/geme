@extends('layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Nuevo depósito</h1>
    <button class="btn btn-sm btn-outline-secondary" onclick="startWarehouseFormTour()">
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

<form action="{{ route('warehouses.store') }}" method="POST" class="mt-3" id="warehouseForm">
    @csrf
    <div class="mb-3" id="wh-code">
        <label class="form-label">Código</label>
        <input type="text" name="code" class="form-control" required>
    </div>
    <div class="mb-3" id="wh-name">
        <label class="form-label">Nombre</label>
        <input type="text" name="name" class="form-control" required>
    </div>
    <div class="mb-3" id="wh-address">
        <label class="form-label">Dirección</label>
        <input type="text" name="address" class="form-control">
    </div>
    <div class="mb-3" id="wh-description">
        <label class="form-label">Descripción</label>
        <textarea name="description" class="form-control"></textarea>
    </div>
    <div class="mb-3" id="wh-order">
        <label class="form-label">Orden</label>
        <input type="number" name="sort_order" class="form-control" value="0">
    </div>
    <div class="form-check mb-3" id="wh-active">
        <input type="checkbox" name="is_active" class="form-check-input" id="is_active" checked value="1">
        <label class="form-check-label" for="is_active">Activo</label>
    </div>
    <button type="submit" class="btn btn-primary">Guardar</button>
    <a href="{{ route('warehouses.index') }}" class="btn btn-secondary">Cancelar</a>
</form>

<script>
    function startWarehouseFormTour() {
        if (typeof introJs === 'undefined') return;
        introJs()
            .setOptions({
                steps: [
                    { element: '#wh-code', intro: 'Código corto y único del depósito. Ej: PRINCIPAL, SUC-01, BOD-A.' },
                    { element: '#wh-name', intro: 'Nombre descriptivo. Ej: Almacén principal, Bodega A.' },
                    { element: '#wh-address', intro: 'Dirección física del depósito. Opcional.' },
                    { element: '#wh-description', intro: 'Notas internas sobre el depósito.' },
                    { element: '#wh-order', intro: 'Orden numérico para ordenar los depósitos en listados.' },
                    { element: '#wh-active', intro: 'Desactiva depósitos que ya no uses. No podrás asignarles ubicaciones nuevas.' }
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
