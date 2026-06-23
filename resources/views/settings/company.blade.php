@extends('layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Datos fiscales de la empresa</h1>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('settings.company.update') }}">
    @csrf

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Razón social</label>
            <input type="text" name="company_name" class="form-control" value="{{ old('company_name', $companyName) }}" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">RIF</label>
            <input type="text" name="company_tax_id" class="form-control" value="{{ old('company_tax_id', $companyTaxId) }}" required placeholder="J123456789">
        </div>
        <div class="col-md-6">
            <label class="form-label">Dirección fiscal</label>
            <input type="text" name="company_address" class="form-control" value="{{ old('company_address', $companyAddress) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Teléfono</label>
            <input type="text" name="company_phone" class="form-control" value="{{ old('company_phone', $companyPhone) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Correo</label>
            <input type="email" name="company_email" class="form-control" value="{{ old('company_email', $companyEmail) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Régimen fiscal</label>
            <select name="fiscal_regime" class="form-select">
                <option value="ORDINARIO" {{ old('fiscal_regime', $fiscalRegime) === 'ORDINARIO' ? 'selected' : '' }}>Ordinario</option>
                <option value="SIMPLIFICADO" {{ old('fiscal_regime', $fiscalRegime) === 'SIMPLIFICADO' ? 'selected' : '' }}>Simplificado</option>
                <option value="ESPECIAL" {{ old('fiscal_regime', $fiscalRegime) === 'ESPECIAL' ? 'selected' : '' }}>Especial</option>
            </select>
        </div>
    </div>

    <div class="mt-4">
        <button type="submit" class="btn btn-primary">Guardar datos fiscales</button>
        <a href="{{ route('fiscal-ledger.index') }}" class="btn btn-outline-secondary">Ir al Libro Electrónico</a>
    </div>
</form>
@endsection
