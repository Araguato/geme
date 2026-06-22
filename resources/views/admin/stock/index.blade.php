@extends('layout')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Stock actual</h1>
        <div>
            <a href="{{ route('stock.adjust.form') }}" class="btn btn-primary">Nuevo ajuste / entrada</a>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="mb-2">
        <strong>Valor total de inventario:</strong>
        $ {{ number_format($totalValue, 2) }}
    </div>

    <table class="table table-striped align-middle">
        <thead>
        <tr>
            <th>Producto</th>
            <th>Unidad</th>
            <th class="text-end">Cantidad</th>
            <th class="text-end">Costo promedio</th>
            <th class="text-end">Valor</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse($stockItems as $item)
            <tr>
                <td>{{ $item->product?->name ?? '—' }}</td>
                <td>{{ $item->unit ?: $item->product?->default_unit }}</td>
                <td class="text-end">{{ number_format($item->quantity, 3) }}</td>
                <td class="text-end">$ {{ number_format($item->average_cost, 4) }}</td>
                <td class="text-end">$ {{ number_format($item->quantity * $item->average_cost, 2) }}</td>
                <td class="text-end">
                    @if($item->product)
                        <a href="{{ route('stock.movements', $item->product) }}" class="btn btn-sm btn-warning">
                            Kardex / movimientos
                        </a>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-muted">Aún no hay movimientos de inventario registrados.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
@endsection
