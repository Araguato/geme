@extends('layout')

@section('content')
<div class="geme-panel">
@php
    $money = fn ($v) => '$ ' . number_format((float) $v, 2, ',', '.');
    $segment = $filters['segmento'] ?? null;
    $firstName = fn ($name) => trim(explode(' ', trim((string) $name))[0] ?? '');
    $waLink = function ($c) use ($message, $firstName) {
        $num = \App\Services\CustomerInsightsService::whatsappNumber($c['phone']);
        if (!$num || !$c['opt_in'] || trim($message) === '') return null;
        return 'https://wa.me/' . $num . '?text=' . rawurlencode(str_replace('{nombre}', $firstName($c['name']), $message));
    };
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0"><i class="bi bi-megaphone"></i> Marketing</h1>
        <div class="text-muted small">Conoce a tus clientes, agrúpalos y lánzales campañas. Solo se contacta a quien dio su consentimiento.</div>
    </div>
    <a href="{{ route('parties.create', ['type' => 'customer']) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-person-plus"></i> Nuevo cliente</a>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

{{-- KPIs --}}
<div class="row g-2 mb-3">
    <div class="col-6 col-lg-3"><div class="card card-body py-2 h-100">
        <div class="text-muted small">Clientes registrados</div>
        <div class="fs-4 fw-bold">{{ $totals['customers'] }}</div>
        <div class="small text-muted">{{ $totals['buyers'] }} con compras</div>
    </div></div>
    <div class="col-6 col-lg-3"><div class="card card-body py-2 h-100">
        <div class="text-muted small">Aceptan promociones</div>
        <div class="fs-4 fw-bold">{{ $totals['opt_in'] }}</div>
        <div class="small text-muted">{{ $totals['customers'] ? number_format($totals['opt_in'] / $totals['customers'] * 100, 0) : 0 }} % de los clientes</div>
    </div></div>
    <div class="col-12 col-lg-6"><div class="card card-body py-2 h-100 {{ $identified['pct_orders'] < 30 ? 'border-warning' : '' }}">
        <div class="text-muted small">Ventas con cliente identificado (90 días)</div>
        <div class="d-flex align-items-center gap-2">
            <div class="fs-4 fw-bold">{{ number_format($identified['pct_orders'], 0) }} %</div>
            <div class="progress flex-grow-1" style="height: 8px;"><div class="progress-bar" style="width: {{ $identified['pct_orders'] }}%"></div></div>
        </div>
        <div class="small text-muted">{{ $identified['identified'] }} de {{ $identified['orders'] }} tickets.
            @if($identified['pct_orders'] < 30)
                <span class="text-warning"><i class="bi bi-lightbulb"></i> Pide el cliente en el TPV para que Marketing tenga datos.</span>
            @endif
        </div>
    </div></div>
</div>

{{-- Segmentos --}}
<div class="row g-2 mb-3">
    @foreach($segments as $key => $s)
        <div class="col-6 col-md-4 col-xl-2">
            <a href="{{ route('marketing.index', array_filter(['segmento' => $segment === $key ? null : $key, 'category_id' => $filters['category_id'] ?? null, 'mensaje' => $message ?: null])) }}"
               class="card card-body py-2 h-100 text-decoration-none {{ $segment === $key ? 'border-primary border-2' : '' }}" title="{{ $s['help'] }}" style="color: inherit;">
                <div class="small"><i class="bi {{ $s['icon'] }}"></i> {{ $s['label'] }}</div>
                <div class="fs-4 fw-bold">{{ $segmentCounts[$key] }}</div>
                <div class="small text-muted" style="font-size: .72rem;">{{ $s['help'] }}</div>
            </a>
        </div>
    @endforeach
</div>

{{-- Filtros y campaña --}}
<form method="GET" action="{{ route('marketing.index') }}" class="card mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1">Segmento</label>
                <select name="segmento" class="form-select form-select-sm">
                    <option value="">Todos los clientes</option>
                    @foreach($segments as $key => $s)
                        <option value="{{ $key }}" @selected($segment === $key)>{{ $s['label'] }} ({{ $segmentCounts[$key] }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Compró la categoría (90 días)</label>
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">Cualquiera</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}" @selected(($filters['category_id'] ?? null) == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Buscar</label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm" placeholder="Nombre, documento, teléfono…">
            </div>
            <div class="col-md-3">
                <div class="form-check mb-1">
                    <input class="form-check-input" type="checkbox" name="con_consentimiento" value="1" id="conConsentimiento" @checked(!empty($filters['con_consentimiento']))>
                    <label class="form-check-label small" for="conConsentimiento">Solo con consentimiento</label>
                </div>
                <button class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel"></i> Filtrar</button>
            </div>
        </div>

        <hr>
        <div class="row g-2 align-items-end">
            <div class="col-lg-8">
                <label class="form-label small mb-1"><i class="bi bi-whatsapp"></i> Mensaje de campaña (opcional). Usa <code>{nombre}</code> para personalizarlo.</label>
                <textarea name="mensaje" rows="2" class="form-control form-control-sm" maxlength="1000"
                          placeholder="Hola {nombre}, esta semana tenemos 10 % en víveres. ¡Te esperamos!">{{ $message }}</textarea>
            </div>
            <div class="col-lg-4 d-grid gap-2">
                <button class="btn btn-success btn-sm"><i class="bi bi-whatsapp"></i> Preparar enlaces de WhatsApp</button>
                <a href="{{ route('marketing.export', array_filter($filters)) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-filetype-csv"></i> Exportar lista ({{ $filteredOptIn }} con consentimiento)</a>
            </div>
        </div>
        <div class="form-text">
            Los enlaces y la exportación solo incluyen a clientes que <strong>aceptaron recibir promociones</strong>.
            El mensaje se abre en tu WhatsApp; GEME no envía nada por su cuenta. Cada exportación queda registrada.
        </div>
    </div>
</form>

{{-- Lista --}}
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between">
        <span>{{ $segment ? $segments[$segment]['label'] : 'Clientes' }}</span>
        <span class="small text-muted fw-normal">{{ $filteredCount }} clientes · {{ $filteredOptIn }} con consentimiento</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Cliente</th>
                    <th>Segmentos</th>
                    <th class="text-end">Compras</th>
                    <th class="text-end">Total neto</th>
                    <th class="text-end">Ticket medio</th>
                    <th>Última compra</th>
                    <th>Categoría favorita</th>
                    <th class="text-center">Promociones</th>
                    @if(trim($message) !== '')<th></th>@endif
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $c)
                    <tr>
                        <td>
                            <a href="{{ route('marketing.customers.show', $c['id']) }}">{{ $c['name'] }}</a>
                            <div class="small text-muted">{{ $c['phone'] ?: ($c['email'] ?: $c['document']) }}</div>
                        </td>
                        <td>
                            @foreach($c['segments'] as $s)
                                <span class="badge rounded-pill bg-secondary bg-opacity-50 fw-normal" title="{{ $segments[$s]['help'] }}"><i class="bi {{ $segments[$s]['icon'] }}"></i> {{ $segments[$s]['label'] }}</span>
                            @endforeach
                        </td>
                        <td class="text-end">{{ $c['orders'] }}</td>
                        <td class="text-end">{{ $money($c['net']) }}</td>
                        <td class="text-end">{{ $c['orders'] ? $money($c['avg_ticket']) : '—' }}</td>
                        <td class="small">
                            @if($c['last_at'])
                                {{ $c['last_at']->format('d/m/Y') }}
                                <div class="text-muted">hace {{ $c['days_since'] }} {{ $c['days_since'] === 1 ? 'día' : 'días' }}</div>
                            @else
                                —
                            @endif
                        </td>
                        <td class="small">{{ $c['favorite_category'] ?? '—' }}</td>
                        <td class="text-center">
                            @if($c['opt_in'])
                                <span class="badge bg-success" title="Acepta promociones desde {{ $c['opt_in_at']?->format('d/m/Y') }}"><i class="bi bi-check"></i> Sí</span>
                            @else
                                <span class="badge bg-secondary bg-opacity-50" title="Sin consentimiento">No</span>
                            @endif
                        </td>
                        @if(trim($message) !== '')
                            <td class="text-end">
                                @if($link = $waLink($c))
                                    <a href="{{ $link }}" target="_blank" rel="noopener" class="btn btn-success btn-sm py-0"><i class="bi bi-whatsapp"></i> Enviar</a>
                                @elseif(!$c['opt_in'])
                                    <span class="small text-muted">Sin consentimiento</span>
                                @else
                                    <span class="small text-muted">Sin teléfono</span>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-muted">No hay clientes con estos filtros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($customers->hasPages())
        <div class="card-footer">{{ $customers->links('pagination::bootstrap-5') }}</div>
    @endif
</div>
</div>
@endsection
