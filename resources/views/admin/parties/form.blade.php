@extends('layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>{{ $party->exists ? 'Editar' : 'Crear' }} tercero</h1>
</div>

<form method="POST" action="{{ route('parties.store', ['redirect' => request('redirect')]) }}">
    @csrf
    <input type="hidden" name="type" value="{{ $type }}">

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Nombre o razón social</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $party->name) }}" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Tipo de documento</label>
            <select name="document_type" class="form-select">
                <option value="RIF" {{ old('document_type', $party->document_type) === 'RIF' ? 'selected' : '' }}>RIF</option>
                <option value="CI" {{ old('document_type', $party->document_type) === 'CI' ? 'selected' : '' }}>Cédula</option>
                <option value="PASAPORTE" {{ old('document_type', $party->document_type) === 'PASAPORTE' ? 'selected' : '' }}>Pasaporte</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Número de documento</label>
            <input type="text" name="document_number" class="form-control" value="{{ old('document_number', $party->document_number) }}" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Teléfono</label>
            <input type="text" name="phone" class="form-control" value="{{ old('phone', $party->phone) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Correo</label>
            <input type="email" name="email" class="form-control" value="{{ old('email', $party->email) }}">
        </div>
        <div class="col-md-4">
            <div class="form-check mt-4">
                <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', $party->is_active ?? true) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">Activo</label>
            </div>
        </div>
        <div class="col-md-12">
            <label class="form-label">Dirección</label>
            <input type="text" name="address" class="form-control" value="{{ old('address', $party->address) }}">
        </div>
        <div class="col-md-12">
            <label class="form-label">Notas</label>
            <textarea name="notes" class="form-control" rows="3">{{ old('notes', $party->notes) }}</textarea>
        </div>
    </div>

    <div class="mt-4">
        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="javascript:history.back()" class="btn btn-outline-secondary">Volver</a>
    </div>
</form>
@endsection
