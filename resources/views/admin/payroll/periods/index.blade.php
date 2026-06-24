@extends('layout')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3" id="payrollPeriodsHeader">
        <div>
            <h1 class="mb-0">Periodos de nómina</h1>
            <p class="text-muted small mb-0">Administra los ciclos de pago y su estado.</p>
        </div>
        <a href="{{ route('payroll-periods.create') }}" class="btn btn-primary">Nuevo periodo</a>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="table-responsive" id="payrollPeriodsTableWrapper">
        <table class="table table-striped align-middle" id="payrollPeriodsTable">
            <thead>
            <tr>
                <th>Nombre</th>
                <th>Tipo</th>
                <th>Rango</th>
                <th>Fecha de pago</th>
                <th class="text-center">Estado</th>
                <th class="text-center">Cerrado por</th>
                <th class="text-end">Acciones</th>
            </tr>
            </thead>
            <tbody>
            @forelse($periods as $period)
                <tr>
                    <td>{{ $period->name ?: '—' }}</td>
                    <td>{{ $typeLabels[$period->period_type] ?? ucfirst($period->period_type) }}</td>
                    <td>
                        {{ optional($period->start_date)->format('d/m/Y') }}
                        &ndash;
                        {{ optional($period->end_date)->format('d/m/Y') }}
                    </td>
                    <td>{{ optional($period->pay_date)->format('d/m/Y') ?: '—' }}</td>
                    <td class="text-center">
                        @php($status = $period->status)
                        @switch($status)
                            @case('closed')
                                <span class="badge bg-success">Cerrado</span>
                                @break
                            @case('open')
                                <span class="badge bg-warning text-dark">Abierto</span>
                                @break
                            @default
                                <span class="badge bg-secondary">Borrador</span>
                        @endswitch
                    </td>
                    <td class="text-center">
                        @if($period->closed_at)
                            <div class="small">
                                {{ $period->closedBy?->name ?? '—' }}<br>
                                <span class="text-muted">{{ optional($period->closed_at)->format('d/m/Y H:i') }}</span>
                            </div>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('payroll-periods.edit', $period) }}" class="btn btn-sm btn-primary">Editar</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-muted">Aún no hay periodos registrados.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $periods->links() }}
@endsection

@push('scripts')
    <script>
        window.GEME_TOUR_STEPS = [
            {
                intro: 'Gestiona tus periodos de nómina: cada ciclo agrupa corridas y entradas de pago.'
            },
            {
                element: '#payrollPeriodsHeader .btn-primary',
                intro: 'Crea un nuevo periodo indicando rango de fechas, tipo (semanal/quincenal) y fecha de pago.'
            },
            {
                element: '#payrollPeriodsTable',
                intro: 'Tabla de periodos con estado, rango y responsable del cierre. Haz clic en “Editar” para ajustar datos.'
            }
        ];
    </script>
@endpush
