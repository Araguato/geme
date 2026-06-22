@extends('layout')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Cuentas por pagar</h1>
        <a href="{{ route('supplier-invoices.create') }}" class="btn btn-primary">Nueva factura</a>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-4">
            <select name="supplier_id" class="form-select" onchange="this.form.submit()">
                <option value="">Todos los proveedores</option>
                @foreach($suppliers as $supplier)
                    @php($party = $supplier->party)
                    <option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>
                        {{ $party?->name ?? 'Proveedor #'.$supplier->id }}
                    </option>
                @endforeach
            </select>
        </div>
    </form>

    <table class="table table-striped align-middle">
        <thead>
        <tr>
            <th>Fecha</th>
            <th>Proveedor</th>
            <th>N° factura</th>
            <th class="text-end">Monto (USD)</th>
            <th class="text-end">Pagado (USD)</th>
            <th class="text-end">Saldo (USD)</th>
            <th>Estado</th>
            <th class="text-end">Acciones</th>
        </tr>
        </thead>
        <tbody>
        @forelse($invoices as $invoice)
            @php($party = $invoice->supplier->party ?? null)
            <tr>
                <td>{{ $invoice->date->format('d/m/Y') }}</td>
                <td>{{ $party?->name ?? 'Proveedor #'.$invoice->supplier_id }}</td>
                <td>{{ $invoice->invoice_number ?: '—' }}</td>
                <td class="text-end">{{ number_format($invoice->amount_usd, 2) }}</td>
                <td class="text-end">{{ number_format($invoice->paid_usd, 2) }}</td>
                <td class="text-end">{{ number_format($invoice->remaining_usd, 2) }}</td>
                <td>
                    @if($invoice->status === 'pagada')
                        <span class="badge bg-success">Pagada</span>
                    @elseif($invoice->status === 'parcial')
                        <span class="badge bg-warning text-dark">Parcial</span>
                    @else
                        <span class="badge bg-danger">Pendiente</span>
                    @endif
                </td>
                <td class="text-end">
                    <a href="{{ route('supplier-invoices.show', $invoice) }}" class="btn btn-sm btn-warning">Ver</a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="text-muted">Aún no hay facturas registradas.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    {{ $invoices->links() }}
@endsection
