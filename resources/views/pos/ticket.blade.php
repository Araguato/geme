@extends('layout')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Ticket / Factura</span>
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
            </div>
        </div>
    </div>

    <style>
        @media print {
            .btn, .card-header { display: none !important; }
            .card { border: none !important; }
        }
    </style>
@endsection
