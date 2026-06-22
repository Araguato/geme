@extends('layout')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="mb-0">{{ $mode === 'edit' ? 'Editar periodo de nómina' : 'Nuevo periodo de nómina' }}</h1>
            <p class="text-muted small mb-0">Define el rango de pago, tipo y estado del ciclo.</p>
        </div>
        <a href="{{ route('payroll-periods.index') }}" class="btn btn-secondary">Volver</a>
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

    <form method="POST" action="{{ $mode === 'edit' ? route('payroll-periods.update', $period) : route('payroll-periods.store') }}" class="card border-0 shadow-sm">
        @csrf
        @if($mode === 'edit')
            @method('PUT')
        @endif

        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label">Nombre interno</label>
                    <input type="text" name="name" class="form-control"
                           value="{{ old('name', $period->name) }}" maxlength="120"
                           placeholder="Ej. Nómina mayo 2026">
                    <div class="form-text">Opcional: ayuda a identificar el periodo en listados y reportes.</div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Tipo de periodo *</label>
                    <select name="period_type" class="form-select" required>
                        @foreach($typeOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('period_type', $period->period_type) === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Estado *</label>
                    <select name="status" class="form-select" required>
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $period->status) === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row g-4 mt-1">
                <div class="col-md-4">
                    <label class="form-label">Fecha inicio *</label>
                    <input type="date" name="start_date" class="form-control" required
                           value="{{ old('start_date', optional($period->start_date)->format('Y-m-d')) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fecha fin *</label>
                    <input type="date" name="end_date" class="form-control" required
                           value="{{ old('end_date', optional($period->end_date)->format('Y-m-d')) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fecha de pago</label>
                    <input type="date" name="pay_date" class="form-control"
                           value="{{ old('pay_date', optional($period->pay_date)->format('Y-m-d')) }}">
                </div>
            </div>

            @if($mode === 'edit' && $period->closed_at)
                <div class="alert alert-info mt-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Periodo cerrado</strong><br>
                            Cerrado el {{ $period->closed_at->format('d/m/Y H:i') }}
                            @if($period->closedBy)
                                por {{ $period->closedBy->name }}
                            @endif
                        </div>
                        <span class="text-muted small">Cambiar el estado a Borrador o Abierto reabrirá el ciclo.</span>
                    </div>
                </div>
            @endif
        </div>

        <div class="card-footer d-flex justify-content-end gap-2 bg-transparent">
            <button type="submit" class="btn btn-primary">
                {{ $mode === 'edit' ? 'Actualizar periodo' : 'Crear periodo' }}
            </button>
        </div>
    </form>
@endsection
