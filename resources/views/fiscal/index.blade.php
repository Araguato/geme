@extends('layout')

@section('content')
@php
    $badge = ['pending' => 'bg-warning text-dark', 'issued' => 'bg-success', 'error' => 'bg-danger', 'contingency' => 'bg-info text-dark', 'cancelled' => 'bg-secondary'];
@endphp
<div class="geme-panel">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0"><i class="bi bi-receipt"></i> Facturación fiscal</h1>
        <div class="text-muted small">Medio actual: {{ $providerLabel }}</div>
    </div>
    @if($enabled)
        <form method="POST" action="{{ route('fiscal-documents.process') }}">
            @csrf
            <button class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-repeat"></i> Procesar pendientes</button>
        </form>
    @endif
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif
@if(!$enabled)
    <div class="alert alert-secondary">No hay medio de emisión configurado: las ventas se registran sin emitir por un medio externo. @if(auth()->user()->hasRole('admin'))<a href="{{ route('settings.fiscal.edit') }}">Configurar</a>@endif</div>
@endif

<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('fiscal-documents.index') }}" class="btn btn-sm {{ $status ? 'btn-outline-secondary' : 'btn-secondary' }}">Todos</a>
    @foreach(\App\Models\FiscalDocument::STATUSES as $st)
        <a href="{{ route('fiscal-documents.index', ['status' => $st]) }}" class="btn btn-sm {{ $status === $st ? 'btn-secondary' : 'btn-outline-secondary' }}">
            {{ \App\Models\FiscalDocument::statusLabel($st) }} <span class="badge bg-light text-dark">{{ $counts[$st] ?? 0 }}</span>
        </a>
    @endforeach
</div>

<div class="table-responsive card">
<table class="table table-sm align-middle mb-0">
    <thead><tr><th>Pedido</th><th>Fecha</th><th class="text-end">Total $</th><th>Estado</th><th>Factura / control</th><th>Intentos</th><th></th></tr></thead>
    <tbody>
    @forelse($docs as $d)
        <tr>
            <td>{{ $d->order?->order_number }}</td>
            <td>{{ $d->order?->created_at?->format('d/m/Y H:i') }}</td>
            <td class="text-end">{{ number_format($d->order?->total ?? 0, 2) }}</td>
            <td>
                <span class="badge {{ $badge[$d->status] ?? 'bg-secondary' }}">{{ \App\Models\FiscalDocument::statusLabel($d->status) }}</span>
                @if($d->environment === 'test')<span class="badge bg-dark">prueba</span>@endif
                @if($d->last_error)<div class="small text-danger">{{ $d->last_error }}</div>@endif
            </td>
            <td class="small">{{ $d->invoice_number ?? '—' }}<br><span class="text-muted">{{ $d->control_number }}</span></td>
            <td>{{ $d->attempts }}</td>
            <td class="text-end">
                @unless($d->isDone())
                    <form method="POST" action="{{ route('fiscal-documents.retry', $d) }}" class="d-inline">@csrf
                        <button class="btn btn-sm btn-outline-primary">Reintentar</button>
                    </form>
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#cont{{ $d->id }}">Talonario</button>
                @endunless
            </td>
        </tr>
        @unless($d->isDone())
            <tr class="collapse" id="cont{{ $d->id }}">
                <td colspan="7" class="bg-body-tertiary">
                    <form method="POST" action="{{ route('fiscal-documents.contingency', $d) }}" class="row g-2 align-items-end">@csrf
                        <div class="col-md-4"><label class="form-label small mb-0">N° de factura (talonario)</label><input name="invoice_number" class="form-control form-control-sm" required></div>
                        <div class="col-md-4"><label class="form-label small mb-0">N° de control</label><input name="control_number" class="form-control form-control-sm" required></div>
                        <div class="col-md-4"><button class="btn btn-sm btn-primary">Registrar contingencia</button></div>
                    </form>
                </td>
            </tr>
        @endunless
    @empty
        <tr><td colspan="7" class="text-center text-muted py-4">No hay documentos fiscales.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div class="mt-3">{{ $docs->links() }}</div>
</div>
@endsection
