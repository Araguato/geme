@extends('layout')

@section('content')
<div class="card mb-3">
    <div class="card-body">
        <h1 class="h4 mb-2">Emitir nota de crédito para pedido {{ $order->order_number }}</h1>
        <p class="small text-muted mb-2">
            Total del pedido: $ {{ number_format($order->total ?? 0, 2) }}
        </p>
        <p class="small text-muted mb-2">
            Total de notas de crédito previas: $ {{ number_format($totalCreditNotes, 2) }}
        </p>
        <p class="small fw-semibold">
            Saldo máximo disponible para nota de crédito: $ {{ number_format($netTotal, 2) }}
        </p>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('orders.credit-notes.store', $order) }}" method="POST" class="row g-3">
            @csrf
            <div class="col-12 col-md-4">
                <label class="form-label">Monto de la nota de crédito</label>
                <input type="number" step="0.01" min="0.01" max="{{ $netTotal }}" name="total" value="{{ old('total') }}" class="form-control" required>
                <div class="form-text">No puede ser mayor que $ {{ number_format($netTotal, 2) }}.</div>
            </div>
            <div class="col-12 col-md-8">
                <label class="form-label">Motivo</label>
                <input type="text" name="reason" value="{{ old('reason') }}" class="form-control" maxlength="255" required>
            </div>
            <div class="col-12 d-flex justify-content-between mt-3">
                <a href="{{ route('orders.edit', $order) }}" class="btn btn-outline-secondary">Volver al pedido</a>
                <button type="submit" class="btn btn-primary" onclick="return confirm('¿Confirmar emisión de la nota de crédito? Esta acción quedará registrada en auditoría.');">Emitir nota de crédito</button>
            </div>
        </form>
    </div>
</div>
@endsection
