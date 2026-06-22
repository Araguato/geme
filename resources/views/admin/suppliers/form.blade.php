@extends('layout')

@section('content')
    <h1>{{ $mode === 'edit' ? 'Editar proveedor' : 'Nuevo proveedor' }}</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ $mode === 'edit' ? route('suppliers.update', $supplier) : route('suppliers.store') }}" method="POST" class="mt-3">
        @csrf
        @if($mode === 'edit')
            @method('PUT')
        @endif

        <div class="row g-3 mb-3">
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

        <div class="row g-3 mb-3">
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

        <div class="row g-3 mb-3">
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

        <div class="mb-3">
            <label class="form-label">Notas internas</label>
            <textarea name="notes" class="form-control" rows="3">{{ old('notes', $party->notes) }}</textarea>
        </div>

        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                   {{ old('is_active', $party->is_active) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Proveedor activo</label>
        </div>

        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="{{ route('suppliers.index') }}" class="btn btn-secondary">Cancelar</a>
    </form>
@endsection
