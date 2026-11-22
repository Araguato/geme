@extends('layout')

@section('content')
@php
    $money = fn ($v) => number_format((float) $v, 2, ',', '.');
    $query = request()->except('group');
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0">Informe de ventas</h1>
        <div class="text-muted small">Del {{ $from->format('d/m/Y') }} al {{ $to->format('d/m/Y') }} · solo ventas completadas</div>
    </div>
    <a href="{{ route('reports.sales.export', request()->query()) }}" class="btn btn-outline-success">
        <i class="bi bi-filetype-csv"></i> Exportar CSV
    </a>
</div>

<form method="GET" action="{{ route('reports.sales') }}" class="card card-body mb-3">
    <input type="hidden" name="group" value="{{ $group }}">
    <div class="row g-2 align-items-end">
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">Desde</label>
            <input type="date" name="from" value="{{ $from->toDateString() }}" class="form-control form-control-sm">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">Hasta</label>
            <input type="date" name="to" value="{{ $to->toDateString() }}" class="form-control form-control-sm">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">Vendedor</label>
            <select name="user_id" class="form-select form-select-sm">
                <option value="">Todos</option>
                @foreach($sellers as $s)
                    <option value="{{ $s->id }}" @selected(($filters['user_id'] ?? null) == $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">Categoría</label>
            <select name="category_id" class="form-select form-select-sm">
                <option value="">Todas</option>
                @foreach($categories as $c)
                    <option value="{{ $c->id }}" @selected(($filters['category_id'] ?? null) == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        @if($salesLocations->count() > 1)
        <div class="col-6 col-md-2">
            <label class="form-label small mb-1">Punto de venta</label>
            <select name="sales_location_id" class="form-select form-select-sm">
                <option value="">Todos</option>
                @foreach($salesLocations as $l)
                    <option value="{{ $l->id }}" @selected(($filters['sales_location_id'] ?? null) == $l->id)>{{ $l->name }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="col-6 col-md-2 d-flex gap-1">
            <button class="btn btn-primary btn-sm flex-grow-1"><i class="bi bi-funnel"></i> Filtrar</button>
            <a href="{{ route('reports.sales', ['group' => $group]) }}" class="btn btn-outline-secondary btn-sm" title="Quitar filtros"><i class="bi bi-x-lg"></i></a>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-1 mt-2">
        @foreach([
            'Hoy' => [now()->toDateString(), now()->toDateString()],
            'Este mes' => [now()->startOfMonth()->toDateString(), now()->toDateString()],
            'Mes anterior' => [now()->subMonthNoOverflow()->startOfMonth()->toDateString(), now()->subMonthNoOverflow()->endOfMonth()->toDateString()],
            'Este año' => [now()->startOfYear()->toDateString(), now()->toDateString()],
        ] as $label => [$f, $t])
            <a href="{{ route('reports.sales', array_merge(request()->query(), ['from' => $f, 'to' => $t])) }}" class="btn btn-light btn-sm border">{{ $label }}</a>
        @endforeach
    </div>
</form>

<div class="row g-2 mb-3">
    <div class="col-6 col-lg-3"><div class="card card-body py-2">
        <div class="text-muted small">Venta neta (sin IVA)</div>
        <div class="fs-4 fw-bold">{{ $money($totals['net']) }}</div>
        <div class="text-muted small">Total con IVA: {{ $money($totals['total']) }}</div>
    </div></div>
    <div class="col-6 col-lg-3"><div class="card card-body py-2">
        <div class="text-muted small">Tickets</div>
        <div class="fs-4 fw-bold">{{ number_format($totals['tickets'], 0, ',', '.') }}</div>
        <div class="text-muted small">Ticket medio: {{ $money($totals['tickets'] ? $totals['total'] / $totals['tickets'] : 0) }}</div>
    </div></div>
    <div class="col-6 col-lg-3"><div class="card card-body py-2">
        <div class="text-muted small">Costo de lo vendido</div>
        <div class="fs-4 fw-bold">{{ $money($totals['cost']) }}</div>
        <div class="text-muted small">IVA: {{ $money($totals['tax']) }}</div>
    </div></div>
    <div class="col-6 col-lg-3"><div class="card card-body py-2">
        <div class="text-muted small">Margen bruto</div>
        <div class="fs-4 fw-bold {{ $totals['margin'] < 0 ? 'text-danger' : 'text-success' }}">{{ $money($totals['margin']) }}</div>
        <div class="text-muted small">{{ number_format($totals['margin_pct'], 1, ',', '.') }} % sobre venta neta</div>
    </div></div>
</div>

<ul class="nav nav-tabs mb-0">
    @foreach($groupLabels as $key => $label)
        <li class="nav-item">
            <a class="nav-link {{ $group === $key ? 'active' : '' }}" href="{{ route('reports.sales', array_merge($query, ['group' => $key])) }}">Por {{ strtolower($label) }}</a>
        </li>
    @endforeach
</ul>

<div class="card border-top-0 rounded-top-0 mb-3">
    <div class="card-body">
        @if($rows->isEmpty())
            <p class="text-muted mb-0">No hay ventas en el período seleccionado.</p>
        @else
            @if($rows->count() > 1)
                <div style="height: {{ min(40 + $rows->take(15)->count() * 28, 460) }}px;" class="mb-3">
                    <canvas id="salesGroupChart"></canvas>
                </div>
            @endif
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ $groupLabel }}</th>
                            <th class="text-end">Tickets</th>
                            @if($group !== 'seller')<th class="text-end">Cantidad</th>@endif
                            <th class="text-end">Venta neta</th>
                            <th class="text-end">IVA</th>
                            <th class="text-end">Total</th>
                            @if($group === 'seller')<th class="text-end">Ticket medio</th>@endif
                            <th class="text-end">Costo</th>
                            <th class="text-end">Margen</th>
                            <th class="text-end">Margen %</th>
                            <th class="text-end" style="width: 12%">% venta</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $r)
                            <tr>
                                <td>
                                    @if($group === 'seller' && $r['id'])
                                        <a href="{{ route('reports.sales', array_merge(request()->query(), ['group' => 'category', 'user_id' => $r['id']])) }}" title="Ver sus ventas por categoría">{{ $r['label'] }}</a>
                                    @elseif($group === 'category' && $r['id'])
                                        <a href="{{ route('reports.sales', array_merge(request()->query(), ['group' => 'product', 'category_id' => $r['id']])) }}" title="Ver productos de la categoría">{{ $r['label'] }}</a>
                                    @else
                                        {{ $r['label'] }}
                                    @endif
                                </td>
                                <td class="text-end">{{ $r['tickets'] }}</td>
                                @if($group !== 'seller')<td class="text-end">{{ rtrim(rtrim(number_format($r['quantity'], 3, ',', '.'), '0'), ',') }}</td>@endif
                                <td class="text-end fw-semibold">{{ $money($r['net']) }}</td>
                                <td class="text-end">{{ $money($r['tax']) }}</td>
                                <td class="text-end">{{ $money($r['total']) }}</td>
                                @if($group === 'seller')<td class="text-end">{{ $money($r['avg_ticket']) }}</td>@endif
                                <td class="text-end">{{ $money($r['cost']) }}</td>
                                <td class="text-end {{ $r['margin'] < 0 ? 'text-danger' : '' }}">{{ $money($r['margin']) }}</td>
                                <td class="text-end">{{ number_format($r['margin_pct'], 1, ',', '.') }} %</td>
                                <td class="text-end">
                                    <div class="d-flex align-items-center justify-content-end gap-1">
                                        <div class="progress flex-grow-1" style="height: 6px; max-width: 60px;">
                                            <div class="progress-bar" style="width: {{ $r['share'] }}%"></div>
                                        </div>
                                        <span class="small">{{ number_format($r['share'], 1, ',', '.') }}</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<p class="text-muted small">
    El costo sale del kardex (costo promedio en el momento de la venta). Para productos sin control de stock
    o preparados con receta se usa el costo actual del producto. Las devoluciones (notas de crédito) no se descuentan.
</p>
@endsection

@push('scripts')
@if($rows->count() > 1)
@php
    $chartRows = $rows->take(15)->map(fn ($r) => ['label' => $r['label'], 'net' => round($r['net'], 2), 'margin' => round($r['margin'], 2)])->values();
@endphp
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    (function () {
        const rows = {{ Js::from($chartRows) }};
        new Chart(document.getElementById('salesGroupChart'), {
            type: 'bar',
            data: {
                labels: rows.map(r => r.label),
                datasets: [
                    { label: 'Venta neta', data: rows.map(r => r.net), backgroundColor: '#2563eb' },
                    { label: 'Margen', data: rows.map(r => r.margin), backgroundColor: '#16a34a' }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: { x: { beginAtZero: true } }
            }
        });
    })();
</script>
@endif
@endpush
