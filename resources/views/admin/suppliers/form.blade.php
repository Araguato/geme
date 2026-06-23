@extends('layout')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>{{ $mode === 'edit' ? 'Editar proveedor' : 'Nuevo proveedor' }}</h1>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="startSupplierFormTour()">
            <i class="bi bi-question-circle"></i> Tour
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

    <form action="{{ $mode === 'edit' ? route('suppliers.update', $supplier) : route('suppliers.store') }}" method="POST" class="mt-3" id="supplier-form">
        @csrf
        @if($mode === 'edit')
            @method('PUT')
        @endif

        <div class="row g-3 mb-3" id="supplierBasic">
            <div class="col-md-6">
                <label class="form-label">Nombre / Razón social *</label>
                <input type="text" name="name" class="form-control" required
                       value="{{ old('name', $party->name) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Tipo documento</label>
                <select name="document_type" class="form-select">
                    <option value="">Sin definir</option>
                    <option value="RIF" {{ old('document_type', $party->document_type) === 'RIF' ? 'selected' : '' }}>RIF</option>
                    <option value="CI" {{ old('document_type', $party->document_type) === 'CI' ? 'selected' : '' }}>CI</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">N° documento</label>
                <input type="text" name="document_number" class="form-control"
                       value="{{ old('document_number', $party->document_number) }}">
            </div>
        </div>

        <div class="row g-3 mb-3" id="supplierContact">
            <div class="col-md-3">
                <label class="form-label">Teléfono</label>
                <input type="text" name="phone" class="form-control"
                       value="{{ old('phone', $party->phone) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Correo electrónico</label>
                <input type="email" name="email" class="form-control"
                       value="{{ old('email', $party->email) }}">
            </div>
            <div class="col-md-5">
                <label class="form-label">Dirección</label>
                <input type="text" name="address" class="form-control"
                       value="{{ old('address', $party->address) }}">
            </div>
        </div>

        <div class="row g-3 mb-3" id="supplierCommercial">
            <div class="col-md-4">
                <label class="form-label">Persona de contacto</label>
                <input type="text" name="contact_name" class="form-control"
                       value="{{ old('contact_name', $supplier->contact_name) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Condición de pago</label>
                <input type="text" name="payment_terms" class="form-control" placeholder="Contado, 7 días, 30 días..."
                       value="{{ old('payment_terms', $supplier->payment_terms) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Moneda por defecto</label>
                <input type="text" name="default_currency" class="form-control" placeholder="USD, Bs..."
                       value="{{ old('default_currency', $supplier->default_currency) }}">
            </div>
        </div>

        <div class="mb-3" id="supplierNotes">
            <label class="form-label">Notas internas</label>
            <textarea name="notes" class="form-control" rows="3">{{ old('notes', $party->notes) }}</textarea>
        </div>

        <div class="form-check form-switch mb-3" id="supplierActive">
            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                   {{ old('is_active', $party->is_active) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Proveedor activo</label>
        </div>

        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="{{ route('suppliers.index') }}" class="btn btn-secondary">Cancelar</a>
    </form>

    <script>
        function startSupplierFormTour() {
            if (typeof introJs === 'undefined') return;
            introJs()
                .setOptions({
                    steps: [
                        { element: '#supplierBasic', intro: 'Datos básicos del proveedor: nombre, tipo de documento y número (RIF o CI). El RIF es necesario para el Libro de Compras SENIAT.' },
                        { element: '#supplierContact', intro: 'Información de contacto: teléfono, correo y dirección.' },
                        { element: '#supplierCommercial', intro: 'Datos comerciales: persona de contacto, condiciones de pago y moneda por defecto.' },
                        { element: '#supplierNotes', intro: 'Notas internas para referencia del equipo.' },
                        { element: '#supplierActive', intro: 'Activa o desactiva el proveedor. Solo los activos aparecen en facturas.' },
                    ],
                    nextLabel: 'Siguiente',
                    prevLabel: 'Anterior',
                    skipLabel: 'Saltar',
                    doneLabel: 'Listo',
                    showProgress: true,
                })
                .start();
        }
    </script>
@endsection
