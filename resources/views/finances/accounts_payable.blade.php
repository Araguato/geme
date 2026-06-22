@extends('layout')

@section('content')
    <h1>Cuentas por pagar</h1>
    <p class="text-muted">Revisa tus gastos pendientes de pago (con fecha de vencimiento y sin fecha de pago).</p>

    <form method="GET" action="{{ route('finances.accounts-payable') }}" class="row g-3 mb-3">
        <div class="col-md-3">
            <label class="form-label">Vencimiento desde</label>
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Vencimiento hasta</label>
            <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Categoría</label>
            <select name="category_id" class="form-select">
                <option value="">Todas</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ (string) $categoryId === (string) $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end justify-content-between">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="{{ route('finances.index') }}" class="btn btn-outline-secondary">Volver a finanzas</a>
        </div>
    </form>

    <div class="mb-3">
        <strong>Total pendiente:</strong>
        {{ number_format($totalOpen, 2) }}
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-sm align-middle">
            <thead>
            <tr>
                <th>Fecha factura</th>
                <th>Fecha vencimiento</th>
                <th>Categoría</th>
                <th class="text-end">Monto</th>
                <th>Nota</th>
                <th style="width: 140px;"></th>
            </tr>
            </thead>
            <tbody>
            @forelse($expenses as $expense)
                <tr @if($expense->due_date && $expense->due_date->isPast()) class="table-warning" @endif>
                    <td>{{ $expense->date?->toDateString() }}</td>
                    <td>{{ $expense->due_date?->toDateString() }}</td>
                    <td>{{ $expense->category?->name }}</td>
                    <td class="text-end">{{ number_format($expense->amount, 2) }}</td>
                    <td>{{ $expense->note }}</td>
                    <td class="text-end">
                        <form action="{{ route('finances.mark-paid', $expense) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('¿Marcar esta cuenta como pagada?');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success">Marcar pagada</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">No hay cuentas por pagar en el filtro seleccionado.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $expenses->links() }}
@endsection
