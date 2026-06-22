@extends('layout')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3" id="payrollRunsHeader">
        <div>
            <h1 class="mb-0">Corridas de nómina</h1>
            <p class="text-muted small mb-0">Historial de ejecuciones y aprobaciones de cada periodo.</p>
        </div>
        <a href="{{ route('payroll-runs.create', array_filter(['period_id' => $currentPeriodId])) }}" class="btn btn-primary">
            Nueva corrida
        </a>
    </div>

    <form method="GET" action="{{ route('payroll-runs.index') }}" class="card border-0 shadow-sm mb-4" id="payrollRunsFilters">
        <div class="card-body row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label">Filtrar por periodo</label>
                <select name="period_id" class="form-select" onchange="this.form.submit()">
                    <option value="">Todos los periodos</option>
                    @foreach($periodOptions as $id => $label)
                        <option value="{{ $id }}" @selected((string)$currentPeriodId === (string)$id)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-secondary w-100">Aplicar filtro</button>
            </div>
            <div class="col-md-3">
                <a href="{{ route('payroll-runs.index') }}" class="btn btn-outline-secondary w-100">Limpiar</a>
            </div>
        </div>
    </form>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="table-responsive" id="payrollRunsTableWrapper">
        <table class="table table-striped align-middle" id="payrollRunsTable">
            <thead>
            <tr>
                <th>Código</th>
                <th>Periodo</th>
                <th>Estado</th>
                <th class="text-center">Entradas</th>
                <th>Procesado</th>
                <th>Aprobado</th>
                <th class="text-end">Acciones</th>
            </tr>
            </thead>
            <tbody>
            @forelse($runs as $run)
                <tr>
                    <td>{{ $run->code ?? 'Sin código' }}</td>
                    <td>
                        <div class="fw-semibold">{{ $run->period?->name ?: 'Periodo #' . $run->payroll_period_id }}</div>
                        <div class="text-muted small">
                            {{ $run->period?->period_type ? ucfirst($run->period->period_type) : '—' }} ·
                            {{ optional($run->period?->start_date)->format('d/m/Y') }}
                            –
                            {{ optional($run->period?->end_date)->format('d/m/Y') }}
                        </div>
                    </td>
                    <td>
                        @php($status = $run->status)
                        @switch($status)
                            @case('approved')
                                <span class="badge bg-success">Aprobado</span>
                                @break
                            @case('processing')
                                <span class="badge bg-warning text-dark">En proceso</span>
                                @break
                            @default
                                <span class="badge bg-secondary">Borrador</span>
                        @endswitch
                    </td>
                    <td class="text-center">
                        <span class="badge bg-dark">
                            {{ $run->entries_count ?? $run->entries?->count() ?? 0 }}
                        </span>
                    </td>
                    <td>
                        @if($run->processed_at)
                            {{ $run->processed_at->format('d/m/Y H:i') }}
                        @else
                            <span class="text-muted">Pendiente</span>
                        @endif
                    </td>
                    <td>
                        @if($run->approved_at)
                            <div>{{ $run->approved_at->format('d/m/Y H:i') }}</div>
                            <div class="text-muted small">por {{ $run->approvedBy?->name ?? '—' }}</div>
                        @else
                            <span class="text-muted">No aprobado</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="btn-group" role="group">
                            <a href="{{ route('payroll-runs.edit', $run) }}" class="btn btn-sm btn-primary">Editar</a>
                            <form action="{{ route('payroll-runs.generate', $run) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-success"
                                        onclick="return confirm('¿Generar entradas para esta corrida?');">
                                    Generar
                                </button>
                            </form>
                            <form action="{{ route('payroll-runs.recalculate', $run) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-warning"
                                        onclick="return confirm('¿Recalcular montos de esta corrida?');">
                                    Recalcular
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-muted">Aún no hay corridas de nómina registradas.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $runs->links() }}
@endsection

@push('scripts')
    <script>
        window.WAWI_TOUR_STEPS = [
            {
                intro: 'Aquí gestionas las corridas de nómina: cada corrida calcula las entradas de pago para un periodo.'
            },
            {
                element: '#payrollRunsHeader .btn-primary',
                intro: 'Crea una nueva corrida para generar entradas de empleados dentro del periodo seleccionado.'
            },
            {
                element: '#payrollRunsFilters',
                intro: 'Filtra por periodo para enfocarte en las corridas activas o históricas.'
            },
            {
                element: '#payrollRunsTable',
                intro: 'Consulta estado, entradas generadas y acciones como generar, recalcular o editar.'
            }
        ];
    </script>
@endpush
