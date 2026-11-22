@extends('layout')

@section('content')
@php
    $money = fn ($v) => number_format((float) $v, 2, ',', '.');
    $methodLabels = ['cash' => 'Efectivo', 'card' => 'Tarjeta', 'transfer' => 'Transferencia', 'pagomovil' => 'Pago Móvil', 'punto' => 'Punto de venta', 'cashea' => 'Cashea (inicial)', 'other' => 'Otro'];
    $monthStr = $month->format('Y-m');
    $range = ['from' => $month->copy()->startOfMonth()->toDateString(), 'to' => $month->copy()->endOfMonth()->toDateString()];
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0">Panel financiero</h1>
        <div class="text-muted small">Hola, {{ auth()->user()->name }} · {{ ucfirst($month->locale('es')->translatedFormat('F Y')) }}
            @if($bcvRate > 0) · Tasa BCV {{ $money($bcvRate) }} Bs/$ @endif</div>
    </div>
    <form method="GET" class="d-flex gap-1">
        <a href="{{ route('accounting.dashboard', ['month' => $month->copy()->subMonthNoOverflow()->format('Y-m')]) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-left"></i></a>
        <input type="month" name="month" value="{{ $monthStr }}" class="form-control form-control-sm" onchange="this.form.submit()">
        <a href="{{ route('accounting.dashboard', ['month' => $month->copy()->addMonthNoOverflow()->format('Y-m')]) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-right"></i></a>
    </form>
</div>

{{-- Accesos directos --}}
<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('reports.sales', $range) }}" class="btn btn-primary btn-sm"><i class="bi bi-bar-chart"></i> Ventas por vendedor / categoría</a>
    <a href="{{ route('fiscal-ledger.index') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-journal-text"></i> Libro SENIAT</a>
    <a href="{{ route('fiscal-ledger.tax-report') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-percent"></i> Resumen de impuestos</a>
    <a href="{{ route('supplier-invoices.index') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-receipt"></i> Facturas de proveedor</a>
    <a href="{{ route('supplier-ap.dashboard') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-cash-coin"></i> Cuentas por pagar</a>
    @if($financesEnabled && $canEditExpenses)
        <a href="{{ route('finances.index') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-wallet2"></i> Gastos</a>
    @endif
    @if($canEditExpenses)
        <a href="{{ route('settings.bcv.edit') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-currency-exchange"></i> Tasa BCV</a>
    @endif
</div>

{{-- KPIs --}}
<div class="row g-2 mb-3">
    <div class="col-6 col-lg-3"><div class="card card-body py-2 h-100">
        <div class="text-muted small">Venta neta del mes</div>
        <div class="fs-4 fw-bold">{{ $money($sales['net']) }}</div>
        <div class="small">
            @if($salesChange !== null)
                <span class="{{ $salesChange >= 0 ? 'text-success' : 'text-danger' }}"><i class="bi {{ $salesChange >= 0 ? 'bi-arrow-up' : 'bi-arrow-down' }}"></i> {{ number_format(abs($salesChange), 1, ',', '.') }} %</span>
                <span class="text-muted">vs. mes anterior</span>
            @else
                <span class="text-muted">{{ $sales['tickets'] }} tickets</span>
            @endif
        </div>
    </div></div>
    <div class="col-6 col-lg-3"><div class="card card-body py-2 h-100">
        <div class="text-muted small">IVA débito (ventas)</div>
        <div class="fs-4 fw-bold">{{ $money($sales['tax']) }}</div>
        <div class="text-muted small">Total facturado: {{ $money($sales['total']) }}</div>
    </div></div>
    <div class="col-6 col-lg-3"><div class="card card-body py-2 h-100">
        <div class="text-muted small">Margen bruto</div>
        <div class="fs-4 fw-bold {{ $sales['margin'] < 0 ? 'text-danger' : 'text-success' }}">{{ $money($sales['margin']) }}</div>
        <div class="text-muted small">{{ number_format($sales['margin_pct'], 1, ',', '.') }} % · costo {{ $money($sales['cost']) }}</div>
    </div></div>
    <div class="col-6 col-lg-3"><div class="card card-body py-2 h-100">
        <div class="text-muted small">Cuentas por pagar abiertas</div>
        <div class="fs-4 fw-bold">{{ $money($payables['remaining_usd']) }} $</div>
        <div class="small {{ $payables['overdue_count'] ? 'text-danger' : 'text-muted' }}">
            {{ $payables['count'] }} facturas · {{ $payables['overdue_count'] }} vencidas ({{ $money($payables['overdue_usd']) }} $)
        </div>
    </div></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header fw-semibold">Ventas diarias</div>
            <div class="card-body" style="height: 260px;">
                @if(count($daily))
                    <canvas id="dailyChart"></canvas>
                @else
                    <p class="text-muted mb-0">Sin ventas en este mes.</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header fw-semibold">Resultado del mes</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between"><span>Venta neta</span><span>{{ $money($sales['net']) }}</span></li>
                <li class="list-group-item d-flex justify-content-between"><span>− Costo de lo vendido</span><span>{{ $money($sales['cost']) }}</span></li>
                <li class="list-group-item d-flex justify-content-between fw-semibold"><span>= Margen bruto</span><span>{{ $money($sales['margin']) }}</span></li>
                @if($expenses !== null)
                    <li class="list-group-item d-flex justify-content-between"><span>− Gastos del negocio</span><span>{{ $money($expenses) }}</span></li>
                    <li class="list-group-item d-flex justify-content-between fw-bold {{ ($sales['margin'] - $expenses) < 0 ? 'text-danger' : 'text-success' }}"><span>= Resultado operativo</span><span>{{ $money($sales['margin'] - $expenses) }}</span></li>
                @endif
                <li class="list-group-item d-flex justify-content-between text-muted small"><span>Compras registradas ({{ (int) ($purchases->count ?? 0) }})</span><span>{{ $money($purchases->amount_usd ?? 0) }} $</span></li>
            </ul>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6 col-lg-4">
        <div class="card h-100">
            <div class="card-header fw-semibold">Cobros por forma de pago</div>
            <ul class="list-group list-group-flush">
                @forelse($paymentsByMethod as $p)
                    <li class="list-group-item d-flex justify-content-between">
                        <span>{{ $methodLabels[$p->method] ?? $p->method }} <span class="text-muted small">({{ $p->count }})</span></span>
                        <span>{{ $money($p->amount) }}</span>
                    </li>
                    @if($p->method === 'cashea' && $p->financed > 0)
                        <li class="list-group-item d-flex justify-content-between small text-muted ps-4"><span>Financiado por Cashea</span><span>{{ $money($p->financed) }}</span></li>
                    @endif
                @empty
                    <li class="list-group-item text-muted">Sin cobros.</li>
                @endforelse
            </ul>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100">
            <div class="card-header fw-semibold d-flex justify-content-between">
                <span>Ventas por vendedor</span>
                <a href="{{ route('reports.sales', $range + ['group' => 'seller']) }}" class="small">Ver todo</a>
            </div>
            <ul class="list-group list-group-flush">
                @forelse($topSellers as $r)
                    <li class="list-group-item d-flex justify-content-between"><span>{{ $r['label'] }}</span><span>{{ $money($r['net']) }} <span class="text-muted small">({{ number_format($r['share'], 0) }} %)</span></span></li>
                @empty
                    <li class="list-group-item text-muted">Sin ventas.</li>
                @endforelse
            </ul>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100">
            <div class="card-header fw-semibold d-flex justify-content-between">
                <span>Ventas por categoría</span>
                <a href="{{ route('reports.sales', $range + ['group' => 'category']) }}" class="small">Ver todo</a>
            </div>
            <ul class="list-group list-group-flush">
                @forelse($topCategories as $r)
                    <li class="list-group-item d-flex justify-content-between"><span>{{ $r['label'] }}</span><span>{{ $money($r['net']) }} <span class="text-muted small">({{ number_format($r['margin_pct'], 0) }} % mg.)</span></span></li>
                @empty
                    <li class="list-group-item text-muted">Sin ventas.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold">Facturas de proveedor vencidas o por vencer (7 días)</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Proveedor</th><th>Factura</th><th>Vence</th><th class="text-end">Pendiente $</th></tr></thead>
                    <tbody>
                        @forelse($dueSoon as $inv)
                            @php($overdue = \Carbon\Carbon::parse($inv->due_date)->lt(today()))
                            <tr class="{{ $overdue ? 'table-danger' : '' }}">
                                <td>{{ $inv->supplier?->party?->name ?? $inv->supplier?->name ?? '—' }}</td>
                                <td><a href="{{ route('supplier-invoices.show', $inv) }}">{{ $inv->invoice_number }}</a></td>
                                <td>{{ \Carbon\Carbon::parse($inv->due_date)->format('d/m/Y') }}</td>
                                <td class="text-end">{{ $money($inv->remaining_usd) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">Nada vencido ni por vencer.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold">Últimos cierres de caja</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Cierre</th><th>Cajero</th><th class="text-end">Apertura</th><th class="text-end">Cobrado</th><th class="text-end">Declarado</th></tr></thead>
                    <tbody>
                        @forelse($shifts as $s)
                            <tr>
                                <td>{{ $s->closed_at->format('d/m H:i') }}@if($s->salesLocation)<div class="text-muted small">{{ $s->salesLocation->name }}</div>@endif</td>
                                <td>{{ $s->user?->name ?? '—' }}</td>
                                <td class="text-end">{{ $money($s->opening_amount) }}</td>
                                <td class="text-end">{{ $money($s->collected ?? 0) }}</td>
                                <td class="text-end">{{ $s->closing_amount !== null ? $money($s->closing_amount) : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted">Sin cierres en este mes.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@if(count($daily))
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    (function () {
        const daily = @json($daily);
        new Chart(document.getElementById('dailyChart'), {
            type: 'bar',
            data: {
                labels: Object.keys(daily).map(d => d.split('-')[2]),
                datasets: [{ label: 'Ventas', data: Object.values(daily), backgroundColor: '#2563eb' }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });
    })();
</script>
@endif
@endpush
