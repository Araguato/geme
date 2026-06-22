@extends('layout')

@section('content')
<h1>Caja / Pago del pedido {{ $order->order_number }}</h1>

<p>
    Tipo: <strong>{{ ucfirst($order->type) }}</strong>
    @if($order->table)
        | Mesa: <strong>{{ $order->table->code }}</strong>
    @elseif($order->type === 'online')
        | Online
    @endif
    | Total: <strong>$ {{ number_format($order->total, 2) }}</strong>
    | Pagado: <strong>$ {{ number_format($totalPaid, 2) }}</strong>
    | Restante: <strong>$ {{ number_format($remaining, 2) }}</strong>
    @if($bcvRate > 0)
        | Tasa BCV: <strong>{{ number_format($bcvRate, 2) }} Bs / USD</strong>
        @if(!is_null($remainingBs))
            | Restante en Bs: <strong>{{ number_format($remainingBs, 2) }} Bs</strong>
        @endif
    @endif
</p>

<div class="row">
    <div class="col-md-6">
        <h3>Registrar nuevo pago</h3>
        <form action="{{ route('orders.payments.store', $order) }}" method="POST" class="mt-3">
            @csrf
            <div class="mb-3">
                <label class="form-label">Monto (USD)</label>
                <input type="number" step="0.01" name="amount" class="form-control" value="{{ $remaining > 0 ? number_format($remaining, 2, '.', '') : '' }}" required>
                <div class="form-text">Por defecto se propone el monto restante del pedido.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Método de pago</label>
                <select name="method" class="form-select" required>
                    <option value="efectivo">Efectivo</option>
                    <option value="tarjeta_visa">Tarjeta Visa</option>
                    <option value="tarjeta_mastercard">Tarjeta Mastercard</option>
                    <option value="tarjeta_credito_debito">Tarjeta crédito/débito</option>
                    <option value="pagomovil">Pagomóvil (en Bs)</option>
                    <option value="pix">PIX</option>
                    <option value="otro">Otro</option>
                </select>
                @if($bcvRate <= 0)
                    <div class="form-text text-danger">Configura BCV_RATE en el .env para calcular Pagomóvil en Bs.</div>
                @endif
            </div>
            <div class="mb-3">
                <label class="form-label">Referencia / Serial (PagoMóvil / PIX)</label>
                <input type="text" name="serial" class="form-control" value="{{ old('serial') }}">
            </div>
            <button type="submit" class="btn btn-primary">Registrar pago</button>
            <a href="{{ route('orders.index') }}" class="btn btn-secondary">Volver a órdenes</a>
        </form>
    </div>

    <div class="col-md-6">
        <h3>Pagos registrados</h3>
        <table class="table table-striped mt-3">
            <thead>
            <tr>
                <th>Fecha</th>
                <th>Método</th>
                <th>Monto USD</th>
                <th>Monto Bs</th>
            </tr>
            </thead>
            <tbody>
            @forelse($order->payments as $payment)
                <tr>
                    <td>{{ $payment->paid_at ?? $payment->created_at }}</td>
                    <td>
                        @if($payment->note === 'pagomovil')
                            Pagomóvil
                        @else
                            {{ $payment->method }}
                        @endif
                    </td>
                    <td>$ {{ number_format($payment->amount, 2) }}</td>
                    <td>
                        @if(!is_null($payment->amount_bs))
                            {{ number_format($payment->amount_bs, 2) }} Bs
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-muted">Aún no hay pagos registrados.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
