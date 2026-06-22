@extends('layout')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Movimientos de inventario: {{ $product->name }}</h1>
        <a href="{{ route('stock.index') }}" class="btn btn-secondary">Volver a stock</a>
    </div>

    <table class="table table-striped align-middle">
        <thead>
        <tr>
            <th>Fecha</th>
            <th>Tipo</th>
            <th>Motivo</th>
            <th class="text-end">Cantidad</th>
            <th>Unidad</th>
            <th class="text-end">Costo unitario</th>
            <th class="text-end">Costo total</th>
            <th class="text-end">Saldo</th>
            <th class="text-end">Costo promedio</th>
        </tr>
        </thead>
        <tbody>
        @forelse($movements as $movement)
            <tr>
                <td>{{ $movement->movement_date->format('Y-m-d H:i') }}</td>
                <td>{{ strtoupper($movement->type) }}</td>
                <td>{{ $movement->reason }}</td>
                <td class="text-end">{{ number_format($movement->quantity, 3) }}</td>
                <td>{{ $movement->unit }}</td>
                <td class="text-end">$ {{ number_format($movement->unit_cost, 4) }}</td>
                <td class="text-end">$ {{ number_format($movement->total_cost, 2) }}</td>
                <td class="text-end">{{ number_format($movement->running_quantity, 3) }}</td>
                <td class="text-end">$ {{ number_format($movement->running_average_cost, 4) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="text-muted">Aún no hay movimientos para este producto.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
@endsection
