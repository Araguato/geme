@extends('layout')

@section('content')
    <h1>{{ $mode === 'edit' ? 'Editar empleado' : 'Nuevo empleado' }}</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ $mode === 'edit' ? route('employees.update', $employee) : route('employees.store') }}" method="POST" class="mt-3">
        @csrf
        @if($mode === 'edit')
            @method('PUT')
        @endif

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">Nombre *</label>
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
                <label class="form-label">Rol / Cargo</label>
                <input type="text" name="role" class="form-control"
                       value="{{ old('role', $employee->role) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Fecha de ingreso</label>
                <input type="date" name="hire_date" class="form-control"
                       value="{{ old('hire_date', optional($employee->hire_date)->format('Y-m-d')) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Tipo de salario</label>
                @php($salaryType = old('salary_type', $employee->salary_type))
                <select name="salary_type" class="form-select">
                    <option value="">Sin definir</option>
                    <option value="mensual" {{ $salaryType === 'mensual' ? 'selected' : '' }}>Mensual</option>
                    <option value="por_hora" {{ $salaryType === 'por_hora' ? 'selected' : '' }}>Por hora</option>
                </select>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">Usuario del sistema (opcional)</label>
                <select name="user_id" class="form-select">
                    <option value="">Sin usuario vinculado</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected(old('user_id', $employee->user_id) == $user->id)>
                            {{ $user->name }} ({{ $user->email }})
                        </option>
                    @endforeach
                </select>
                <div class="form-text">Sirve para saber qué usuario del sistema corresponde a este empleado.</div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label">Salario mensual (USD)</label>
                <input type="number" name="monthly_salary" step="0.01" min="0" class="form-control"
                       value="{{ old('monthly_salary', $employee->monthly_salary) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Pago por hora (USD)</label>
                <input type="number" name="hourly_rate" step="0.01" min="0" class="form-control"
                       value="{{ old('hourly_rate', $employee->hourly_rate) }}">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Notas internas</label>
            <textarea name="notes" class="form-control" rows="3">{{ old('notes', $party->notes) }}</textarea>
        </div>

        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                   {{ old('is_active', $party->is_active) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Empleado activo</label>
        </div>

        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" id="is_current" name="is_current" value="1"
                   {{ old('is_current', $employee->is_current ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_current">Actualmente en nómina</label>
        </div>

        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="{{ route('employees.index') }}" class="btn btn-secondary">Cancelar</a>
    </form>
@endsection
