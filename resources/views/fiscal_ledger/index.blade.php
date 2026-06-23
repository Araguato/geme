@extends('layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Libro Electrónico — {{ $entryType === 'entrada' ? 'Compras' : 'Ventas' }}</h1>
    <div>
        <a href="{{ route('fiscal-ledger.tax-report') }}" class="btn btn-outline-primary me-2">Resumen de impuestos</a>
        <a href="{{ route('fiscal-ledger.export-xml', ['entry_type' => $entryType, 'period' => $period]) }}" class="btn btn-success">
            <i class="bi bi-download"></i> Descargar XML SENIAT
        </a>
    </div>
</div>

<div class="alert alert-info">
    <strong>Contribuyente:</strong> {{ $companyName }} (RIF {{ $companyTaxId }}) — Período {{ $period }}
</div>

<form method="GET" action="{{ route('fiscal-ledger.index') }}" class="row g-2 align-items-end mb-3">
    <input type="hidden" name="entry_type" value="{{ $entryType }}">
    <div class="col-md-3">
        <label class="form-label">Período</label>
        <select name="period" class="form-select" onchange="this.form.submit()">
            @foreach($periods as $p)
                <option value="{{ $p }}" {{ $p === $period ? 'selected' : '' }}>{{ $p }}</option>
            @endforeach
            @if(!$periods->contains($period))
                <option value="{{ $period }}" selected>{{ $period }}</option>
            @endif
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Libro</label>
        <select name="entry_type" class="form-select" onchange="this.form.submit()">
            <option value="entrada" {{ $entryType === 'entrada' ? 'selected' : '' }}>Libro de Compras</option>
            <option value="salida" {{ $entryType === 'salida' ? 'selected' : '' }}>Libro de Ventas</option>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Buscar</label>
        <input type="text" name="search" class="form-control" value="{{ $search ?? '' }}" placeholder="RIF, nombre, número, control...">
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-outline-secondary w-100">Filtrar</button>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead class="table-dark">
            <tr>
                <th>Fecha</th>
                <th>Tipo</th>
                <th>Número</th>
                <th>Control</th>
                <th>RIF</th>
                <th>Nombre</th>
                <th class="text-end">Total</th>
                <th class="text-end">Exento</th>
                <th class="text-end">Base</th>
                <th class="text-end">%</th>
                <th class="text-end">IVA</th>
                <th class="text-end">Ret.</th>
                <th>Hash</th>
            </tr>
        </thead>
        <tbody>
            @forelse($entries as $entry)
                <tr>
                    <td>{{ $entry->document_date?->format('d/m/Y') }}</td>
                    <td>{{ $entry->document_type }}</td>
                    <td>{{ $entry->document_number }}</td>
                    <td>{{ $entry->control_number }}</td>
                    <td>{{ $entry->partner_tax_id }}</td>
                    <td>{{ Str::limit($entry->partner_name, 30) }}</td>
                    <td class="text-end">{{ number_format($entry->total_amount, 2) }}</td>
                    <td class="text-end">{{ number_format($entry->exempt_amount, 2) }}</td>
                    <td class="text-end">{{ number_format($entry->taxable_amount, 2) }}</td>
                    <td class="text-end">{{ number_format($entry->tax_rate, 2) }}</td>
                    <td class="text-end">{{ number_format($entry->tax_amount, 2) }}</td>
                    <td class="text-end">{{ number_format($entry->withholding_amount, 2) }}</td>
                    <td><code class="small">{{ Str::limit($entry->fiscal_hash, 12) }}</code></td>
                </tr>
            @empty
                <tr>
                    <td colspan="13" class="text-center text-muted">No hay registros para este período.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot class="table-group-divider">
            <tr>
                <th colspan="6">Totales</th>
                <th class="text-end">{{ number_format($entries->sum('total_amount'), 2) }}</th>
                <th class="text-end">{{ number_format($entries->sum('exempt_amount'), 2) }}</th>
                <th class="text-end">{{ number_format($entries->sum('taxable_amount'), 2) }}</th>
                <th></th>
                <th class="text-end">{{ number_format($entries->sum('tax_amount'), 2) }}</th>
                <th class="text-end">{{ number_format($entries->sum('withholding_amount'), 2) }}</th>
                <th></th>
            </tr>
        </tfoot>
    </table>
</div>

{{ $entries->withQueryString()->links() }}
@endsection
