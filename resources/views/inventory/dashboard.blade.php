@extends('layout')

@section('content')
@php
    $qty = fn ($v) => rtrim(rtrim(number_format((float) $v, 3, ',', '.'), '0'), ',');
    $reasons = ['sale' => 'Venta', 'purchase' => 'Compra', 'purchase_edit' => 'Corrección de compra', 'initial_stock' => 'Stock inicial', 'recipe' => 'Consumo receta'];
    $types = ['in' => ['Entrada', 'success'], 'out' => ['Salida', 'danger'], 'adjustment' => ['Ajuste', 'secondary']];
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0">Almacén</h1>
        <div class="text-muted small">Hola, {{ auth()->user()->name }} · {{ now()->format('d/m/Y') }}</div>
    </div>
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('stock.adjust.form') }}" class="btn btn-primary"><i class="bi bi-arrow-left-right"></i> Entrada / salida / ajuste</a>
    <a href="{{ route('stock.index') }}" class="btn btn-outline-primary"><i class="bi bi-clipboard-data"></i> Existencias</a>
    <a href="{{ route('products.search') }}" class="btn btn-outline-primary"><i class="bi bi-upc-scan"></i> Buscar producto</a>
    <a href="{{ route('products.index') }}" class="btn btn-outline-primary"><i class="bi bi-box-seam"></i> Productos</a>
    <a href="{{ route('products.labels.bulk') }}" class="btn btn-outline-secondary"><i class="bi bi-tags"></i> Etiquetas</a>
    <a href="{{ route('warehouses.index') }}" class="btn btn-outline-secondary"><i class="bi bi-building"></i> Depósitos</a>
</div>

<div class="row g-2 mb-3">
    <div class="col-6 col-lg-3"><div class="card card-body py-2">
        <div class="text-muted small">Productos con control de stock</div>
        <div class="fs-4 fw-bold">{{ $stats['tracked'] }}</div>
    </div></div>
    <div class="col-6 col-lg-3"><div class="card card-body py-2 {{ $stats['low'] ? 'border-warning' : '' }}">
        <div class="text-muted small">Bajo el mínimo</div>
        <div class="fs-4 fw-bold {{ $stats['low'] ? 'text-warning' : '' }}">{{ $stats['low'] }}</div>
    </div></div>
    <div class="col-6 col-lg-3"><div class="card card-body py-2 {{ $stats['out'] ? 'border-danger' : '' }}">
        <div class="text-muted small">Sin existencia</div>
        <div class="fs-4 fw-bold {{ $stats['out'] ? 'text-danger' : '' }}">{{ $stats['out'] }}</div>
    </div></div>
    <div class="col-6 col-lg-3"><div class="card card-body py-2">
        <div class="text-muted small">Movimientos hoy</div>
        <div class="fs-4 fw-bold">{{ $stats['movements_today'] }}</div>
    </div></div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold">Reponer: stock bajo el mínimo</div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light"><tr><th>Producto</th><th>Categoría</th><th class="text-end">Existencia</th><th class="text-end">Mínimo</th><th></th></tr></thead>
                    <tbody>
                        @forelse($lowStock as $p)
                            <tr>
                                <td>{{ $p->name }}@if($p->sku)<div class="text-muted small">{{ $p->sku }}</div>@endif</td>
                                <td class="small">{{ $p->category?->name }}</td>
                                <td class="text-end fw-semibold {{ $p->stock_quantity <= 0 ? 'text-danger' : 'text-warning' }}">{{ $qty($p->stock_quantity) }} {{ $p->default_unit }}</td>
                                <td class="text-end">{{ $qty($p->reorder_point) }}</td>
                                <td class="text-end"><a href="{{ route('stock.movements', $p) }}" class="btn btn-link btn-sm p-0" title="Kardex"><i class="bi bi-list-ul"></i></a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted">Todo está por encima del mínimo.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($stats['low'] > $lowStock->count())
                <div class="card-footer small"><a href="{{ route('stock.index') }}">Ver los {{ $stats['low'] }} productos</a></div>
            @endif
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header fw-semibold">Últimos movimientos</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead class="table-light"><tr><th>Fecha</th><th>Producto</th><th>Tipo</th><th class="text-end">Cant.</th><th>Usuario</th></tr></thead>
                    <tbody>
                        @forelse($movements as $m)
                            @php([$typeLabel, $typeColor] = $types[$m->type] ?? [$m->type, 'secondary'])
                            <tr>
                                <td class="small text-nowrap">{{ \Carbon\Carbon::parse($m->movement_date)->format('d/m H:i') }}</td>
                                <td>{{ $m->product?->name ?? '—' }}<div class="text-muted small">{{ $reasons[$m->reason] ?? $m->reason }}@if($m->warehouse) · {{ $m->warehouse->name }}@endif</div></td>
                                <td><span class="badge bg-{{ $typeColor }}">{{ $typeLabel }}</span></td>
                                <td class="text-end">{{ $qty($m->quantity) }}</td>
                                <td class="small">{{ $m->user?->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted">Sin movimientos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-header fw-semibold">Compras de los últimos 7 días</div>
            <ul class="list-group list-group-flush">
                @forelse($recentPurchases as $inv)
                    <li class="list-group-item d-flex justify-content-between">
                        <span>{{ $inv->supplier?->party?->name ?? '—' }} <span class="text-muted small">· Fact. {{ $inv->invoice_number }}</span></span>
                        <span class="small text-muted">{{ \Carbon\Carbon::parse($inv->date)->format('d/m/Y') }}</span>
                    </li>
                @empty
                    <li class="list-group-item text-muted">No hay compras recientes.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
