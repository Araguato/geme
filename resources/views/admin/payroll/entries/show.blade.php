@extends('layout')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="mb-0">Detalle de entrada de nómina</h1>
            <p class="text-muted small mb-0">Revisión de conceptos calculados para el empleado.</p>
        </div>
        <a href="{{ route('payroll-entries.index', ['run_id' => $entry->payroll_run_id]) }}" class="btn btn-secondary">
            Volver a la lista
        </a>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-4">
                    <h6 class="text-muted text-uppercase fw-semibold">Empleado</h6>
                    <p class="mb-1 fw-semibold">{{ $entry->employee?->party?->name ?? '—' }}</p>
                    <p class="text-muted small mb-0">
                        Documento: {{ $entry->employee?->party?->document_number ?? 'Sin documento' }}<br>
                        Usuario: {{ $entry->employee?->user?->name ?? 'No vinculado' }}
                    </p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted text-uppercase fw-semibold">Corrida</h6>
                    <p class="mb-1 fw-semibold">{{ $entry->run?->code ?? 'Corrida #' . $entry->payroll_run_id }}</p>
                    <p class="text-muted small mb-0">
                        Periodo: {{ optional($entry->run?->period?->start_date)->format('d/m/Y') }} –
                        {{ optional($entry->run?->period?->end_date)->format('d/m/Y') }}<br>
                        Estado corrida: {{ ucfirst($entry->run?->status ?? '—') }}
                    </p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted text-uppercase fw-semibold">Montos totales</h6>
                    <ul class="list-unstyled mb-0">
                        <li>Base salarial: <strong>{{ number_format($entry->base_salary_amount, 2) }}</strong></li>
                        <li>Ingresos: <strong>{{ number_format($entry->earnings_total, 2) }}</strong></li>
                        <li>Deducciones: <strong>{{ number_format($entry->deductions_total, 2) }}</strong></li>
                        <li>Contribuciones: <strong>{{ number_format($entry->contributions_total, 2) }}</strong></li>
                        <li>Pago neto: <strong class="text-success">{{ number_format($entry->net_pay, 2) }}</strong></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h5 class="mb-3">Conceptos aplicados</h5>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                    <tr>
                        <th>Concepto</th>
                        <th>Tipo</th>
                        <th class="text-end">Cantidad</th>
                        <th class="text-end">Tarifa</th>
                        <th class="text-end">Monto</th>
                        <th class="text-center">Tributa</th>
                        <th class="text-center">Seguridad social</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($entry->items as $item)
                        <tr>
                            <td>{{ $item->concept?->name ?? '—' }}</td>
                            <td>{{ ucfirst($item->type) }}</td>
                            <td class="text-end">{{ $item->quantity !== null ? number_format($item->quantity, 2) : '—' }}</td>
                            <td class="text-end">{{ $item->rate !== null ? number_format($item->rate, 4) : '—' }}</td>
                            <td class="text-end fw-semibold">{{ number_format($item->amount, 2) }}</td>
                            <td class="text-center">
                                <span class="badge {{ $item->is_taxable ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $item->is_taxable ? 'Sí' : 'No' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $item->is_social_security_applicable ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $item->is_social_security_applicable ? 'Sí' : 'No' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-muted">No se registraron conceptos para esta entrada.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
