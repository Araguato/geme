@extends('layout')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="mb-0">{{ $mode === 'edit' ? 'Editar corrida de nómina' : 'Nueva corrida de nómina' }}</h1>
            <p class="text-muted small mb-0">Selecciona el periodo y el estado de la ejecución.</p>
        </div>
        <a href="{{ route('payroll-runs.index', $mode === 'edit' ? ['period_id' => $run->payroll_period_id] : []) }}" class="btn btn-secondary">
            Volver
        </a>
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

    <form method="POST" action="{{ $mode === 'edit' ? route('payroll-runs.update', $run) : route('payroll-runs.store') }}" class="card border-0 shadow-sm">
        @csrf
        @if($mode === 'edit')
            @method('PUT')
        @endif

        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label">Periodo *</label>
                    <select name="payroll_period_id" class="form-select" required {{ $mode === 'edit' ? 'disabled' : '' }}>
                        <option value="">Selecciona un periodo</option>
                        @foreach($periodOptions as $id => $label)
                            <option value="{{ $id }}" @selected(old('payroll_period_id', $run->payroll_period_id) == $id)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Cada corrida pertenece a un único periodo de nómina.</div>
                    @if($mode === 'edit')
                        <input type="hidden" name="payroll_period_id" value="{{ $run->payroll_period_id }}">
                    @endif
                </div>

                <div class="col-md-3">
                    <label class="form-label">Código</label>
                    <input type="text" name="code" class="form-control" maxlength="50"
                           value="{{ old('code', $run->code) }}"
                           placeholder="Se autogenera si se deja en blanco">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Estado *</label>
                    <select name="status" class="form-select" required>
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $run->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if($mode === 'edit')
                <div class="row g-4 mt-1">
                    <div class="col-md-4">
                        <label class="form-label">Procesado el</label>
                        <input type="text" class="form-control" value="{{ $run->processed_at?->format('d/m/Y H:i') ?? 'Pendiente' }}" disabled>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Aprobado el</label>
                        <input type="text" class="form-control" value="{{ $run->approved_at?->format('d/m/Y H:i') ?? 'No aprobado' }}" disabled>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Aprobado por</label>
                        <input type="text" class="form-control" value="{{ $run->approvedBy?->name ?? '—' }}" disabled>
                    </div>
                </div>
            @endif
        </div>

        <div class="card-footer d-flex justify-content-end gap-2 bg-transparent">
            <button type="submit" class="btn btn-primary">
                {{ $mode === 'edit' ? 'Actualizar corrida' : 'Crear corrida' }}
            </button>
        </div>
    </form>
@endsection
