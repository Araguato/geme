@extends('layout')

@section('content')
<div class="card mb-3">
    <div class="card-body">
        <h1 class="h4 mb-1">Notas de entrega pendientes de facturar</h1>
        <p class="small text-muted mb-0">
            Una nota de entrega no es un documento fiscal: no entra al libro de ventas hasta que se factura.
            Al facturar se usa la fecha de hoy y la tasa BCV del día.
        </p>
    </div>
</div>

<div class="card mb-4">
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th>Nota</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Cajero</th>
                    <th class="text-end">Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($notes as $note)
                <tr>
                    <td><a href="{{ route('pos.ticket', $note) }}">{{ $note->delivery_note_number }}</a></td>
                    <td>{{ $note->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $note->customerParty?->name ?? $note->customer_name ?? 'CONSUMIDOR FINAL' }}</td>
                    <td>{{ $note->user?->name ?? 'N/A' }}</td>
                    <td class="text-end">$ {{ number_format($note->total, 2) }}</td>
                    <td class="text-end">
                        <form method="POST" action="{{ route('delivery-notes.invoice', $note) }}"
                              onsubmit="return confirm('¿Facturar esta nota de entrega? Entrará al libro de ventas con la fecha de hoy.');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-primary">Facturar</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-3">No hay notas de entrega pendientes.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($notes->hasPages())
        <div class="card-footer">{{ $notes->links() }}</div>
    @endif
</div>

@if($invoiced->isNotEmpty())
<h2 class="h6">Facturadas recientemente</h2>
<div class="card">
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead><tr><th>Nota</th><th>Venta</th><th>Facturada</th><th>Por</th><th class="text-end">Total</th></tr></thead>
            <tbody>
            @foreach($invoiced as $o)
                <tr>
                    <td>{{ $o->delivery_note_number }}</td>
                    <td><a href="{{ route('pos.ticket', $o) }}">{{ $o->order_number }}</a></td>
                    <td>{{ $o->invoiced_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $o->invoicer?->name ?? 'N/A' }}</td>
                    <td class="text-end">$ {{ number_format($o->total, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
