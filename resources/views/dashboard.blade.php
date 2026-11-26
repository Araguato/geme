@extends('layout')

@section('content')
@php
    $user = auth()->user();
    $isAdmin = $user->hasRole('admin');
    $canSell = $user->hasAnyRole(['admin', 'supervisor', 'cajero', 'vendedor']);
    $canManageInventory = $user->hasAnyRole(['admin', 'supervisor', 'almacenista']);
    $canSeeFinance = $user->hasAnyRole(['admin', 'supervisor', 'contable']);

    $themeVariant = \App\Models\Setting::get('theme_variant', 'classic');
    $isLight = $themeVariant === 'light';
    $accent = $isLight ? '#38bdf8' : (\App\Models\Setting::get('theme_accent_color', '#22c55e') ?: '#22c55e');

    $money = fn ($v) => '$ ' . number_format((float) $v, 2, ',', '.');
    $pct = fn ($v) => $v === null ? '—' : number_format($v, 1, ',', '.') . ' %';
    $methodLabels = ['cash' => 'Efectivo', 'card' => 'Tarjeta', 'transfer' => 'Transferencia', 'pagomovil' => 'Pago Móvil', 'punto' => 'Punto de venta', 'cashea' => 'Cashea', 'other' => 'Otro'];

    // Accesos según el rol (misma matriz que routes/web.php)
    $moduleGroups = [];
    $sales = [];
    if ($canSell) {
        $sales[] = ['route' => route('pos.index'), 'label' => 'TPV', 'icon' => 'bi-cart-check'];
    }
    $sales[] = ['route' => route('products.search'), 'label' => 'Buscar productos', 'icon' => 'bi-upc-scan'];
    $sales[] = ['route' => route('catalog.index'), 'label' => 'Catálogo público', 'icon' => 'bi-shop', 'target' => '_blank'];
    $sales[] = ['route' => route('help.index'), 'label' => 'Centro de ayuda', 'icon' => 'bi-question-circle'];
    if ($user->hasAnyRole(['admin', 'supervisor'])) {
        $sales[] = ['route' => route('marketing.index'), 'label' => 'Marketing y clientes', 'icon' => 'bi-megaphone'];
    }
    $moduleGroups['Ventas y operaciones'] = $sales;
    if ($canManageInventory) {
        $moduleGroups['Inventario'] = [
            ['route' => route('inventory.dashboard'), 'label' => 'Panel de almacén', 'icon' => 'bi-speedometer2'],
            ['route' => route('products.index'), 'label' => 'Productos', 'icon' => 'bi-box-seam'],
            ['route' => route('stock.index'), 'label' => 'Inventario / stock', 'icon' => 'bi-clipboard-data'],
            ['route' => route('warehouses.index'), 'label' => 'Depósitos', 'icon' => 'bi-building'],
        ];
    }
    if ($canSeeFinance) {
        $moduleGroups['Finanzas e informes'] = [
            ['route' => route('reports.sales'), 'label' => 'Ventas por vendedor / categoría', 'icon' => 'bi-bar-chart'],
            ['route' => route('accounting.dashboard'), 'label' => 'Panel financiero', 'icon' => 'bi-calculator'],
            ['route' => route('fiscal-ledger.index'), 'label' => 'Libro SENIAT', 'icon' => 'bi-journal-text'],
            ['route' => route('supplier-ap.dashboard'), 'label' => 'Cuentas por pagar', 'icon' => 'bi-cash-coin'],
        ];
    }
    if ($isAdmin) {
        $moduleGroups['Administración'] = [
            ['route' => route('users.index'), 'label' => 'Usuarios', 'icon' => 'bi-person-gear'],
            ['route' => route('payroll-periods.index'), 'label' => 'Nómina', 'icon' => 'bi-wallet2'],
            ['route' => route('employees.index'), 'label' => 'Empleados', 'icon' => 'bi-people'],
            ['route' => route('suppliers.index'), 'label' => 'Proveedores', 'icon' => 'bi-truck'],
            ['route' => route('settings.appearance.edit'), 'label' => 'Apariencia', 'icon' => 'bi-palette'],
            ['route' => route('settings.ai.edit'), 'label' => 'Inteligencia artificial', 'icon' => 'bi-stars'],
        ];
    }
@endphp
@if($isAdmin)
    @include('dashboard.onboarding')
@endif

<style>
    .dash { --dash-accent: {{ $accent }}; }
    .dash .kpi { position: relative; overflow: hidden; border-radius: 1rem; }
    .dash .kpi .kpi-icon {
        width: 2.6rem; height: 2.6rem; border-radius: .8rem; display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem; background: color-mix(in srgb, var(--dash-accent) 18%, transparent); color: var(--dash-accent);
    }
    .dash .kpi .kpi-value { font-size: 1.65rem; font-weight: 700; line-height: 1.1; letter-spacing: -.01em; }
    .dash .kpi .kpi-label { font-size: .8rem; text-transform: uppercase; letter-spacing: .05em; opacity: .7; }
    .dash .delta { display: inline-flex; align-items: center; gap: .2rem; font-size: .78rem; font-weight: 600; padding: .1rem .5rem; border-radius: 999px; }
    .dash .delta.up { background: rgba(34,197,94,.15); color: {{ $isLight ? '#15803d' : '#4ade80' }}; }
    .dash .delta.down { background: rgba(239,68,68,.15); color: {{ $isLight ? '#b91c1c' : '#f87171' }}; }
    .dash .delta.flat { background: rgba(148,163,184,.18); }
    .dash .card { border-radius: 1rem; box-shadow: 0 .25rem 1rem rgba(0,0,0,.08); }
    .dash .card-header { border-top-left-radius: 1rem !important; border-top-right-radius: 1rem !important; font-weight: 600; }
    .dash .rank-row + .rank-row { border-top: 1px solid rgba(148,163,184,.18); }
    .dash .rank-bar { height: 5px; border-radius: 3px; background: rgba(148,163,184,.2); overflow: hidden; }
    .dash .rank-bar > span { display: block; height: 100%; background: var(--dash-accent); border-radius: 3px; }
    .dash .module-link {
        display: flex; align-items: center; gap: .6rem; padding: .7rem .9rem; border-radius: .75rem; text-decoration: none;
        border: 1px solid rgba(148,163,184,.25); color: inherit; transition: background-color .12s, border-color .12s, transform .12s;
    }
    .dash .module-link:hover { border-color: var(--dash-accent); background: color-mix(in srgb, var(--dash-accent) 10%, transparent); color: inherit; transform: translateY(-1px); }
    .dash .module-link i { color: var(--dash-accent); font-size: 1.15rem; }
    .dash .section-title { font-size: .8rem; text-transform: uppercase; letter-spacing: .06em; opacity: .7; font-weight: 600; }
    .dash .list-clean { list-style: none; margin: 0; padding: 0; }
</style>

<div class="dash geme-panel">
    {{-- Encabezado --}}
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0">Hola, {{ $user->name }}</h1>
            <div class="text-muted small">{{ ucfirst(now()->locale('es')->translatedFormat('l j \d\e F, H:i')) }}</div>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            @if($activeShift)
                <span class="badge rounded-pill bg-success"><i class="bi bi-check-circle"></i> Caja abierta</span>
            @elseif($canSell)
                <span class="badge rounded-pill bg-warning text-dark"><i class="bi bi-exclamation-triangle"></i> Caja cerrada</span>
            @endif
            @if($canSell)
                <a href="{{ route('pos.index') }}" class="btn btn-primary btn-sm"><i class="bi bi-cart-check"></i> {{ $activeShift ? 'Ir al TPV' : 'Abrir caja' }}</a>
            @endif
            @if($isAnalyst)
                <a href="{{ route('reports.sales') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-bar-chart"></i> Informe de ventas</a>
            @endif
        </div>
    </div>

    @if($isAnalyst)
        @php
            $delta = function ($v, $suffix = 'vs. mes anterior') {
                if ($v === null) return '<span class="delta flat">sin comparación</span>';
                $cls = $v > 0.5 ? 'up' : ($v < -0.5 ? 'down' : 'flat');
                $icon = $v > 0.5 ? 'bi-arrow-up-right' : ($v < -0.5 ? 'bi-arrow-down-right' : 'bi-dash');
                return '<span class="delta ' . $cls . '"><i class="bi ' . $icon . '"></i>' . number_format(abs($v), 1, ',', '.') . ' %</span> <span class="small text-muted">' . e($suffix) . '</span>';
            };
            $ticketChange = $prevAvgTicket > 0 ? round(($avgTicket - $prevAvgTicket) / $prevAvgTicket * 100, 1) : null;
        @endphp

        {{-- KPIs --}}
        <div class="row g-3 mb-3">
            <div class="col-6 col-xl-3">
                <div class="card kpi h-100"><div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="kpi-label">Ventas hoy</span><span class="kpi-icon"><i class="bi bi-cash-stack"></i></span>
                    </div>
                    <div class="kpi-value">{{ $money($today['total']) }}</div>
                    <div class="mt-1">{!! $delta($todayChange, 'vs. ayer a esta hora') !!}</div>
                    <div class="small text-muted mt-1">{{ $today['tickets'] }} tickets</div>
                </div></div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="card kpi h-100"><div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="kpi-label">Ventas del mes</span><span class="kpi-icon"><i class="bi bi-graph-up-arrow"></i></span>
                    </div>
                    <div class="kpi-value">{{ $money($month['total']) }}</div>
                    <div class="mt-1">{!! $delta($monthChange) !!}</div>
                    <div class="small text-muted mt-1">Neto {{ $money($month['net']) }} · {{ $month['tickets'] }} tickets</div>
                </div></div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="card kpi h-100"><div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="kpi-label">Margen bruto del mes</span><span class="kpi-icon"><i class="bi bi-pie-chart"></i></span>
                    </div>
                    @if($month['margin_pct'] === null && $month['net'] > 0)
                        <div class="kpi-value text-warning">Sin costos</div>
                        <div class="small text-muted mt-1">Los productos vendidos no tienen costo registrado.</div>
                    @else
                        <div class="kpi-value {{ $month['margin'] < 0 ? 'text-danger' : '' }}">{{ $money($month['margin']) }}</div>
                        <div class="small text-muted mt-1">{{ $pct($month['margin_pct']) }} sobre la venta con costo</div>
                        @if($month['net'] > 0 && $month['cost_coverage'] < 100)
                            <div class="small text-warning"><i class="bi bi-exclamation-triangle"></i> {{ number_format(100 - $month['cost_coverage'], 0) }} % de la venta sin costo</div>
                        @endif
                    @endif
                </div></div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="card kpi h-100"><div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="kpi-label">Ticket medio</span><span class="kpi-icon"><i class="bi bi-receipt"></i></span>
                    </div>
                    <div class="kpi-value">{{ $money($avgTicket) }}</div>
                    <div class="mt-1">{!! $delta($ticketChange) !!}</div>
                    <div class="small text-muted mt-1">
                        <a href="{{ route('supplier-ap.dashboard') }}">{{ $payables['count'] }} facturas por pagar</a>
                        @if($payables['overdue'])<span class="text-danger">· {{ $payables['overdue'] }} vencidas</span>@endif
                    </div>
                </div></div>
            </div>
        </div>

        {{-- Gráficos --}}
        <div class="row g-3 mb-3">
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Ventas por día</span>
                        <span class="small text-muted fw-normal">Este mes frente al mes anterior</span>
                    </div>
                    <div class="card-body" style="height: 300px;"><canvas id="dailyChart"></canvas></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header">Formas de pago (mes)</div>
                    <div class="card-body d-flex align-items-center justify-content-center" style="height: 300px;">
                        @if(count($paymentMethods))
                            <canvas id="paymentsChart"></canvas>
                        @else
                            <span class="text-muted">Sin cobros este mes.</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Rankings --}}
        <div class="row g-3 mb-3">
            @foreach([
                ['title' => 'Por vendedor', 'icon' => 'bi-person-badge', 'rows' => $topSellers, 'group' => 'seller'],
                ['title' => 'Por categoría', 'icon' => 'bi-tags', 'rows' => $topCategories, 'group' => 'category'],
                ['title' => 'Productos más vendidos', 'icon' => 'bi-trophy', 'rows' => $topProducts, 'group' => 'product'],
            ] as $block)
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="bi {{ $block['icon'] }}"></i> {{ $block['title'] }}</span>
                            <a href="{{ route('reports.sales', ['group' => $block['group']]) }}" class="small fw-normal">Ver todo</a>
                        </div>
                        <div class="card-body py-2">
                            @forelse($block['rows'] as $r)
                                <div class="rank-row py-2">
                                    <div class="d-flex justify-content-between gap-2">
                                        <span class="text-truncate">{{ $r['label'] }}</span>
                                        <span class="fw-semibold text-nowrap">{{ $money($r['net']) }}</span>
                                    </div>
                                    <div class="rank-bar my-1"><span style="width: {{ $r['share'] }}%"></span></div>
                                    <div class="small text-muted">
                                        {{ number_format($r['share'], 1, ',', '.') }} % de la venta ·
                                        @if($r['margin_pct'] === null)
                                            <span class="text-warning">sin costo</span>
                                        @else
                                            margen {{ $pct($r['margin_pct']) }}
                                        @endif
                                        @if($block['group'] === 'product') · {{ rtrim(rtrim(number_format($r['quantity'], 2, ',', '.'), '0'), ',') }} uds.@endif
                                        @if($block['group'] === 'seller') · {{ $r['tickets'] }} tickets @endif
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted mb-0 py-2">Sin ventas este mes.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Ventas por hora (mes)</span><span class="small text-muted fw-normal">Horas pico</span>
                    </div>
                    <div class="card-body" style="height: 240px;"><canvas id="hourlyChart"></canvas></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Últimas ventas</span><span class="small text-muted fw-normal">{{ $today['tickets'] }} hoy</span>
                    </div>
                    <div class="card-body py-2"><ul class="list-clean">
                        @forelse($recentSales as $o)
                            <li class="rank-row py-2 d-flex justify-content-between gap-2">
                                <span class="text-truncate">
                                    <span class="small text-muted">{{ $o->created_at->format('d/m H:i') }}</span>
                                    {{ $o->user?->name ?? '—' }}
                                </span>
                                <span class="fw-semibold text-nowrap">{{ $money($o->total) }}</span>
                            </li>
                        @empty
                            <li class="text-muted py-2">Aún no hay ventas.</li>
                        @endforelse
                    </ul></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Stock bajo el mínimo</span>
                        <span class="badge rounded-pill {{ $lowStockCount ? 'bg-danger' : 'bg-success' }}">{{ $lowStockCount }}</span>
                    </div>
                    <div class="card-body py-2"><ul class="list-clean">
                        @forelse($lowStockProducts as $p)
                            <li class="rank-row py-2 d-flex justify-content-between gap-2">
                                <span class="text-truncate">{{ $p->name }}</span>
                                <span class="text-nowrap small">
                                    <strong class="{{ $p->stock_quantity <= 0 ? 'text-danger' : 'text-warning' }}">{{ rtrim(rtrim(number_format($p->stock_quantity, 2, ',', '.'), '0'), ',') }}</strong>
                                    <span class="text-muted">/ mín. {{ rtrim(rtrim(number_format($p->reorder_point, 2, ',', '.'), '0'), ',') }}</span>
                                </span>
                            </li>
                        @empty
                            <li class="text-muted py-2">Todo por encima del mínimo.</li>
                        @endforelse
                        @if($lowStockCount > $lowStockProducts->count())
                            <li class="pt-2 small"><a href="{{ route('inventory.dashboard') }}">Ver los {{ $lowStockCount }} productos</a></li>
                        @endif
                    </ul></div>
                </div>
            </div>
        </div>
    @endif

    {{-- Accesos rápidos --}}
    <div class="mb-2 section-title">{{ $isAnalyst ? 'Accesos rápidos' : 'Módulos' }}</div>
    <div class="row g-3 mb-3">
        @foreach($moduleGroups as $groupName => $mods)
            <div class="col-md-6 {{ count($moduleGroups) > 2 ? 'col-xl-3' : 'col-xl-6' }}">
                <div class="card h-100">
                    <div class="card-header small text-uppercase" style="letter-spacing: .05em;">{{ $groupName }}</div>
                    <div class="card-body d-grid gap-2">
                        @foreach($mods as $m)
                            <a href="{{ $m['route'] }}" class="module-link" @if(!empty($m['target'])) target="{{ $m['target'] }}" @endif>
                                <i class="bi {{ $m['icon'] }}"></i><span>{{ $m['label'] }}</span><i class="bi bi-chevron-right ms-auto small opacity-50"></i>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection

@if($isAnalyst)
@push('scripts')
@php
    $paymentChart = [
        'labels' => array_map(fn ($m) => $methodLabels[$m] ?? $m, array_keys($paymentMethods)),
        'values' => array_values($paymentMethods),
    ];
@endphp
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    (function () {
        if (typeof Chart === 'undefined') return;
        const accent = @json($accent);
        const isLight = @json($isLight);
        const grid = isLight ? 'rgba(15,23,42,.08)' : 'rgba(148,163,184,.15)';
        const text = isLight ? '#334155' : '#cbd5e1';
        Chart.defaults.color = text;
        Chart.defaults.font.family = 'inherit';
        const money = v => '$ ' + Number(v).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        const daily = {{ Js::from($daily) }};
        new Chart(document.getElementById('dailyChart'), {
            data: {
                labels: daily.labels,
                datasets: [
                    { type: 'bar', label: 'Este mes', data: daily.current, backgroundColor: accent, borderRadius: 6, maxBarThickness: 28, order: 2 },
                    { type: 'line', label: 'Mes anterior', data: daily.previous, borderColor: 'rgba(148,163,184,.9)', borderDash: [5, 4], pointRadius: 0, tension: .3, fill: false, order: 1 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } }, tooltip: { callbacks: { title: i => 'Día ' + i[0].label, label: c => c.dataset.label + ': ' + money(c.parsed.y) } } },
                scales: { y: { beginAtZero: true, grid: { color: grid }, ticks: { callback: v => '$' + v } }, x: { grid: { display: false } } }
            }
        });

        const payments = {{ Js::from($paymentChart) }};
        if (document.getElementById('paymentsChart')) {
            new Chart(document.getElementById('paymentsChart'), {
                type: 'doughnut',
                data: { labels: payments.labels, datasets: [{ data: payments.values, backgroundColor: [accent, '#6366f1', '#f59e0b', '#ec4899', '#14b8a6', '#94a3b8', '#ef4444'], borderWidth: 0 }] },
                options: { responsive: true, maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } }, tooltip: { callbacks: { label: c => c.label + ': ' + money(c.parsed) } } } }
            });
        }

        const hourly = {{ Js::from($hourly) }};
        new Chart(document.getElementById('hourlyChart'), {
            type: 'bar',
            data: { labels: hourly.labels, datasets: [{ label: 'Ventas', data: hourly.values, backgroundColor: accent + 'aa', borderRadius: 4 }] },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => money(c.parsed.y) } } },
                scales: { y: { beginAtZero: true, grid: { color: grid }, ticks: { callback: v => '$' + v } }, x: { grid: { display: false } } }
            }
        });
    })();
</script>
@endpush
@endif
