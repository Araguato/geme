@extends('layout')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>{{ $order->is_delivery_note ? 'Nota de entrega' : 'Ticket / Factura' }}</span>
                    <div>
                        <a href="{{ route('pos.index') }}" class="btn btn-sm btn-outline-primary">Nueva venta</a>
                        <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">Imprimir</button>
                    </div>
                </div>
                <div class="card-body text-center" id="ticket">
                    <h5>{{ $order->salesLocation?->name ?? 'Punto de venta' }}</h5>
                    <p class="mb-1 small text-muted">{{ $order->salesLocation?->address ?? '' }}</p>
                    <p class="mb-1 small text-muted">Tel: {{ $order->salesLocation?->phone ?? '' }}</p>
                    <hr>
                    @if($order->status === 'cancelled')
                        <div class="alert alert-danger py-2 mb-2">
                            <strong>VENTA ANULADA</strong>
                            @if($order->voided_at)
                                <div class="small">{{ $order->voided_at->format('d/m/Y H:i') }}@if($order->voider) · {{ $order->voider->name }}@endif</div>
                            @endif
                            @if($order->void_reason)
                                <div class="small">Motivo: {{ $order->void_reason }}</div>
                            @endif
                            @php($creditNote = $order->creditNotes()->latest('id')->first())
                            @if($creditNote)
                                <div class="small">Nota de crédito: {{ $creditNote->number }}</div>
                            @endif
                        </div>
                    @endif
                    @if($order->is_delivery_note)
                        <div class="fw-bold">NOTA DE ENTREGA N° {{ $order->delivery_note_number }}</div>
                        <div class="small text-danger">Documento sin validez fiscal · Pendiente de facturar</div>
                    @elseif($order->delivery_note_number)
                        <div class="small text-muted">Facturada el {{ $order->invoiced_at?->format('d/m/Y H:i') }} (antes nota de entrega {{ $order->delivery_note_number }})</div>
                    @endif
                    <p class="mb-1"><strong>{{ $order->order_number }}</strong></p>
                    <p class="mb-1 small text-muted">{{ $order->created_at->format('d/m/Y H:i') }}</p>
                    <p class="mb-1 small text-muted">Cajero: {{ $order->user?->name ?? 'N/A' }}</p>
                    <hr>
                    <table class="table table-sm text-start">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="text-center">Cant.</th>
                                <th class="text-end">Precio</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                                <tr>
                                    <td>{{ $item->name }}</td>
                                    <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                                    <td class="text-end">$ {{ number_format($item->unit_price, 2) }}</td>
                                    <td class="text-end">$ {{ number_format($item->total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-between fw-bold fs-5 border-top pt-2">
                        <span>Total</span>
                        <span>$ {{ number_format($order->total, 2) }}</span>
                    </div>
                    @if($order->exchange_rate)
                        <div class="d-flex justify-content-between small text-muted pt-1">
                            <span>Equivalente en Bs (tasa {{ number_format($order->exchange_rate, 4, ',', '.') }})</span>
                            <span>Bs {{ number_format($order->total * $order->exchange_rate, 2, ',', '.') }}</span>
                        </div>
                    @endif
                    @php($fdoc = $order->fiscalDocument)
                    @if($fdoc && $fdoc->invoice_number)
                        <div class="border-top mt-2 pt-2 small">
                            @if($fdoc->environment === 'test')
                                <div class="fw-bold text-danger">DOCUMENTO DE PRUEBA · SIN VALIDEZ FISCAL</div>
                            @endif
                            <div>Factura N° <strong>{{ $fdoc->invoice_number }}</strong></div>
                            <div>N° de control <strong>{{ $fdoc->control_number }}</strong></div>
                        </div>
                    @elseif($fdoc)
                        <div class="border-top mt-2 pt-2 small text-muted">Factura fiscal pendiente de emisión.</div>
                    @endif
                </div>
                @if($order->isPendingDeliveryNote() && auth()->user()?->hasAnyRole(['admin', 'supervisor', 'contable']))
                    <div class="card-footer">
                        <form method="POST" action="{{ route('delivery-notes.invoice', $order) }}"
                              onsubmit="return confirm('¿Facturar esta nota de entrega? Entrará al libro de ventas con la fecha de hoy.');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-primary w-100">Convertir en factura</button>
                        </form>
                    </div>
                @endif
                @if($order->status === 'completed' && auth()->user()?->hasAnyRole(['admin', 'supervisor']))
                    <div class="card-footer">
                        <form method="POST" action="{{ route('orders.void', $order) }}"
                              onsubmit="return confirm('¿Anular esta venta? Se devolverá el inventario y se emitirá una nota de crédito. No se puede deshacer.');">
                            @csrf
                            <label class="form-label small mb-1" for="void-reason">Anular venta (nota de crédito)</label>
                            <div class="input-group input-group-sm">
                                <input type="text" id="void-reason" name="reason" class="form-control" maxlength="255" minlength="5" required placeholder="Motivo de la anulación">
                                <button type="submit" class="btn btn-outline-danger">Anular</button>
                            </div>
                            @error('reason')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <style>
        @media print {
            .btn, .card-header, .card-footer { display: none !important; }
            .card { border: none !important; }
        }
    </style>
@endsection
