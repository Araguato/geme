@extends('layout')

@section('content')
<div class="geme-panel">
@php
    $money = fn ($v) => '$ ' . number_format((float) $v, 2, ',', '.');
    $qty = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');
    $sourceLabels = ['tpv' => 'TPV', 'registro_web' => 'registro en la tienda en línea', 'ficha' => 'ficha del cliente'];
    $statusLabels = ['completed' => ['Completada', 'success'], 'pending' => ['Pendiente', 'warning'], 'cancelled' => ['Anulada', 'secondary']];
@endphp

<div class="mb-2"><a href="{{ route('marketing.index') }}" class="small"><i class="bi bi-arrow-left"></i> Marketing</a></div>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">{{ $party->name }}</h1>
        <div class="small text-muted">
            {{ trim($party->document_type . ' ' . $party->document_number) ?: 'Sin documento' }}
            @if($party->phone) · <i class="bi bi-telephone"></i> {{ $party->phone }} @endif
            @if($party->email) · <i class="bi bi-envelope"></i> {{ $party->email }} @endif
        </div>
        <div class="mt-1">
            @foreach($stats['segments'] ?? [] as $s)
                <span class="badge rounded-pill bg-secondary bg-opacity-50 fw-normal"><i class="bi {{ $segments[$s]['icon'] }}"></i> {{ $segments[$s]['label'] }}</span>
            @endforeach
        </div>
    </div>
    <div class="text-end">
        @if($party->marketing_opt_in)
            <div class="small text-success mb-1"><i class="bi bi-check-circle-fill"></i> Acepta promociones
                desde {{ $party->marketing_opt_in_at?->format('d/m/Y') }}
                ({{ $sourceLabels[$party->marketing_opt_in_source] ?? $party->marketing_opt_in_source }})</div>
            <div class="d-flex gap-1 justify-content-end">
                @if($whatsapp)
                    <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" class="btn btn-success btn-sm"><i class="bi bi-whatsapp"></i> WhatsApp</a>
                @endif
                <form method="POST" action="{{ route('marketing.customers.consent', $party) }}" onsubmit="return confirm('¿Retirar el consentimiento de marketing de este cliente?')">
                    @csrf <input type="hidden" name="opt_in" value="0">
                    <button class="btn btn-outline-secondary btn-sm">Retirar consentimiento</button>
                </form>
            </div>
        @else
            <div class="small text-muted mb-1"><i class="bi bi-dash-circle"></i> No ha aceptado recibir promociones</div>
            <form method="POST" action="{{ route('marketing.customers.consent', $party) }}" onsubmit="return confirm('¿El cliente te ha dicho que acepta recibir promociones por WhatsApp o correo?')">
                @csrf <input type="hidden" name="opt_in" value="1">
                <button class="btn btn-outline-success btn-sm"><i class="bi bi-check2"></i> El cliente acepta promociones</button>
            </form>
        @endif
    </div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<div class="row g-2 mb-3">
    <div class="col-6 col-lg-3"><div class="card card-body py-2 h-100">
        <div class="text-muted small">Compras</div>
        <div class="fs-4 fw-bold">{{ $stats['orders'] ?? 0 }}</div>
        <div class="small text-muted">{{ $stats['orders_90'] ?? 0 }} en los últimos 90 días</div>
    </div></div>
    <div class="col-6 col-lg-3"><div class="card card-body py-2 h-100">
        <div class="text-muted small">Total comprado (neto)</div>
        <div class="fs-4 fw-bold">{{ $money($stats['net'] ?? 0) }}</div>
        <div class="small text-muted">{{ $money($stats['net_365'] ?? 0) }} en 12 meses</div>
    </div></div>
    <div class="col-6 col-lg-3"><div class="card card-body py-2 h-100">
        <div class="text-muted small">Ticket medio</div>
        <div class="fs-4 fw-bold">{{ $money($stats['avg_ticket'] ?? 0) }}</div>
        <div class="small text-muted">Favorita: {{ $stats['favorite_category'] ?? '—' }}</div>
    </div></div>
    <div class="col-6 col-lg-3"><div class="card card-body py-2 h-100">
        <div class="text-muted small">Última compra</div>
        <div class="fs-4 fw-bold">{{ ($stats['last_at'] ?? null)?->format('d/m/Y') ?? '—' }}</div>
        <div class="small text-muted">
            @if(isset($stats['days_since']))hace {{ $stats['days_since'] }} {{ $stats['days_since'] === 1 ? 'día' : 'días' }} · @endif
            cliente desde {{ ($stats['first_at'] ?? null)?->format('m/Y') ?? $party->created_at?->format('m/Y') }}
        </div>
    </div></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">Compras por mes (últimos 12 meses)</div>
            <div class="card-body" style="height: 240px;"><canvas id="customerMonthly"></canvas></div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header">Lo que más compra</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Producto</th><th class="text-end">Cant.</th><th class="text-end">Veces</th><th class="text-end">Neto</th></tr></thead>
                    <tbody>
                        @forelse($topProducts as $p)
                            <tr><td>{{ $p->name }}</td><td class="text-end">{{ $qty($p->qty) }}</td><td class="text-end">{{ $p->times }}</td><td class="text-end">{{ $money($p->net) }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">Sin compras registradas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">Historial de compras (últimas 30)</div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light"><tr><th>Fecha</th><th>Ticket</th><th>Canal</th><th>Atendió</th><th class="text-end">Artículos</th><th class="text-end">Total</th><th>Estado</th></tr></thead>
            <tbody>
                @forelse($orders as $o)
                    @php([$sl, $sc] = $statusLabels[$o->status] ?? [$o->status, 'secondary'])
                    <tr>
                        <td class="small">{{ $o->created_at->format('d/m/Y H:i') }}</td>
                        <td class="small">{{ $o->order_number }}</td>
                        <td class="small">{{ $o->type === 'online' ? 'En línea' : 'TPV' }}</td>
                        <td class="small">{{ $o->user?->name ?? '—' }}</td>
                        <td class="text-end">{{ $o->items_count }}</td>
                        <td class="text-end">{{ $money($o->total) }}</td>
                        <td><span class="badge bg-{{ $sc }}">{{ $sl }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-muted">Este cliente aún no tiene compras.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    (function () {
        if (typeof Chart === 'undefined') return;
        const months = {{ Js::from($months) }};
        new Chart(document.getElementById('customerMonthly'), {
            type: 'bar',
            data: { labels: Object.keys(months).map(m => m.slice(5) + '/' + m.slice(2, 4)), datasets: [{ label: 'Neto', data: Object.values(months), backgroundColor: '#2563eb', borderRadius: 4 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });
    })();
</script>
@endpush
