@extends('layout')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3" id="payrollEntriesHeader">
        <div>
            <h1 class="mb-0">Entradas de nómina</h1>
            <p class="text-muted small mb-0">Resumen de pagos por empleado dentro de cada corrida.</p>
        </div>
        <a href="{{ route('payroll-runs.index') }}" class="btn btn-secondary">Ver corridas</a>
    </div>

    <form method="GET" action="{{ route('payroll-entries.index') }}" class="card border-0 shadow-sm mb-4" id="payrollEntriesFilters">
        <div class="card-body row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label">Filtrar por corrida</label>
                <select name="run_id" class="form-select" onchange="this.form.submit()">
                    <option value="">Todas las corridas</option>
                    @foreach($runOptions as $id => $label)
                        <option value="{{ $id }}" @selected((string)$currentRunId === (string)$id)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-secondary w-100">Aplicar filtro</button>
            </div>
            <div class="col-md-3">
                <a href="{{ route('payroll-entries.index') }}" class="btn btn-outline-secondary w-100">Limpiar</a>
            </div>
        </div>
    </form>

    <div class="table-responsive" id="payrollEntriesTableWrapper">
        <table class="table table-striped align-middle" id="payrollEntriesTable">
            <thead>
            <tr>
                <th>Empleado</th>
                <th>Corrida</th>
                <th class="text-end">Ingresos</th>
                <th class="text-end">Deducciones</th>
                <th class="text-end">Contribuciones</th>
                <th class="text-end">Pago neto</th>
                <th class="text-end">Estado</th>
                <th class="text-end">Acciones</th>
            </tr>
            </thead>
            <tbody>
            @forelse($entries as $entry)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $entry->employee?->party?->name ?? '—' }}</div>
                        <div class="text-muted small">{{ $entry->employee?->party?->document_number ?? 'Sin documento' }}</div>
                    </td>
                    <td>
                        <div>{{ $entry->run?->code ?? 'Corrida #' . $entry->payroll_run_id }}</div>
                        <div class="text-muted small">
                            {{ optional($entry->run?->period?->start_date)->format('d/m/Y') }} –
                            {{ optional($entry->run?->period?->end_date)->format('d/m/Y') }}
                        </div>
                    </td>
                    <td class="text-end">{{ number_format($entry->earnings_total, 2) }}</td>
                    <td class="text-end">{{ number_format($entry->deductions_total, 2) }}</td>
                    <td class="text-end">{{ number_format($entry->contributions_total, 2) }}</td>
                    <td class="text-end fw-semibold">{{ number_format($entry->net_pay, 2) }}</td>
                    <td class="text-end">
                        @switch($entry->status)
                            @case('approved')
                                <span class="badge bg-success">Aprobado</span>
                                @break
                            @case('draft')
                                <span class="badge bg-secondary">Borrador</span>
                                @break
                            @default
                                <span class="badge bg-warning text-dark">{{ ucfirst($entry->status) }}</span>
                        @endswitch
                    </td>
                    <td class="text-end">
                        <a href="{{ route('payroll-entries.show', $entry) }}" class="btn btn-sm btn-primary">Ver detalle</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-muted">No hay entradas para mostrar.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $entries->links() }}
@endsection

@push('scripts')
    <script>
        window.GEME_TOUR_STEPS = [
            {
                intro: 'Visualiza las entradas de nómina por empleado: montos brutos, deducciones y pago neto.'
            },
            {
                element: '#payrollEntriesHeader .btn-secondary',
                intro: 'Regresa a las corridas para generar, recalcular o aprobar antes de editar entradas.'
            },
            {
                element: '#payrollEntriesFilters',
                intro: 'Filtra por corrida para enfocarte en un periodo o lote de pago específico.'
            },
            {
                element: '#payrollEntriesTable',
                intro: 'Listado detallado de cada empleado con totales y estado. Usa “Ver detalle” para ajustar manualmente.'
            }
        ];
    </script>
@endpush
