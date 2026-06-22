@extends('layout')

@section('content')
    <h1>Cuentas por pagar - Resumen</h1>

    <div class="row g-3 mb-4 mt-2">
        <div class="col-md-4">
            <div class="card bg-dark text-white h-100">
                <div class="card-body">
                    <div class="text-muted">Total facturado (USD)</div>
                    <div class="fs-3">{{ number_format($totalUsd, 2) }}</div>
                    <div class="text-muted small">Equivalente Bs (BCV {{ number_format($bcvRate, 2) }}):
                        {{ number_format($totalUsd * $bcvRate, 2) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-dark text-white h-100">
                <div class="card-body">
                    <div class="text-muted">Total pagado (USD)</div>
                    <div class="fs-3">{{ number_format($totalPaidUsd, 2) }}</div>
                    <div class="text-muted small">Equivalente Bs:
                        {{ number_format($totalPaidUsd * $bcvRate, 2) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-dark text-white h-100">
                <div class="card-body">
                    <div class="text-muted">Saldo pendiente (USD)</div>
                    <div class="fs-3">{{ number_format($totalRemainingUsd, 2) }}</div>
                    <div class="text-muted small">Equivalente Bs:
                        {{ number_format($totalRemainingUsd * $bcvRate, 2) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <h2 class="mt-4">Por proveedor</h2>

    <table class="table table-striped align-middle mt-2">
        <thead>
        <tr>
            <th>Proveedor</th>
            <th class="text-end">Total facturas (USD)</th>
            <th class="text-end">Pagado (USD)</th>
            <th class="text-end">Pendiente (USD)</th>
            <th class="text-end">Pendiente (Bs)</th>
            <th>Facturas abiertas</th>
            <th class="text-end">Acciones</th>
        </tr>
        </thead>
        <tbody>
        @forelse($bySupplier as $row)
            @php($party = $row['party'])
            <tr>
                <td>{{ $party?->name ?? 'Proveedor #'.$row['supplier']->id }}</td>
                <td class="text-end">{{ number_format($row['total_amount_usd'], 2) }}</td>
                <td class="text-end">{{ number_format($row['total_paid_usd'], 2) }}</td>
                <td class="text-end">{{ number_format($row['total_remaining_usd'], 2) }}</td>
                <td class="text-end">{{ number_format($row['total_remaining_usd'] * $bcvRate, 2) }}</td>
                <td>{{ $row['invoice_count'] }}</td>
                <td class="text-end">
                    <a href="{{ route('supplier-invoices.index', ['supplier_id' => $row['supplier']->id]) }}" class="btn btn-sm btn-warning">Ver facturas</a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-muted">No hay facturas pendientes.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
@endsection
