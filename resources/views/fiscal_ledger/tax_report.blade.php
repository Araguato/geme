@extends('layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Resumen de impuestos por período</h1>
    <a href="{{ route('fiscal-ledger.index') }}" class="btn btn-outline-secondary">Volver al Libro Electrónico</a>
</div>

<div class="alert alert-info">
    <strong>Contribuyente:</strong> {{ $companyName }} (RIF {{ $companyTaxId }}) — Año {{ $year }}
</div>

<form method="GET" action="{{ route('fiscal-ledger.tax-report') }}" class="row g-2 mb-3">
    <div class="col-md-3">
        <label class="form-label">Año</label>
        <select name="year" class="form-select" onchange="this.form.submit()">
            @foreach($years as $y)
                <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
            @endforeach
            @if(!$years->contains((string)$year))
                <option value="{{ $year }}" selected>{{ $year }}</option>
            @endif
        </select>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead class="table-dark">
            <tr>
                <th>Período</th>
                <th>Libro</th>
                <th class="text-end">Ops</th>
                <th class="text-end">Total</th>
                <th class="text-end">Exento</th>
                <th class="text-end">Exonerado</th>
                <th class="text-end">No sujeto</th>
                <th class="text-end">Base</th>
                <th class="text-end">IVA</th>
                <th class="text-end">Ret.</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totals = ['entrada' => ['ops' => 0, 'total' => 0, 'exempt' => 0, 'exonerated' => 0, 'non_subject' => 0, 'taxable' => 0, 'tax' => 0, 'withholding' => 0], 'salida' => ['ops' => 0, 'total' => 0, 'exempt' => 0, 'exonerated' => 0, 'non_subject' => 0, 'taxable' => 0, 'tax' => 0, 'withholding' => 0]];
            @endphp
            @forelse($periods as $period => $row)
                @foreach(['entrada' => 'Compras', 'salida' => 'Ventas'] as $type => $label)
                    @php
                        $entry = $row[$type];
                        if ($entry) {
                            $totals[$type]['ops'] += $entry->operations;
                            $totals[$type]['total'] += $entry->total;
                            $totals[$type]['exempt'] += $entry->exempt;
                            $totals[$type]['exonerated'] += $entry->exonerated;
                            $totals[$type]['non_subject'] += $entry->non_subject;
                            $totals[$type]['taxable'] += $entry->taxable;
                            $totals[$type]['tax'] += $entry->tax;
                            $totals[$type]['withholding'] += $entry->withholding;
                        }
                    @endphp
                    <tr>
                        <td>{{ $period }}</td>
                        <td>{{ $label }}</td>
                        <td class="text-end">{{ $entry ? $entry->operations : 0 }}</td>
                        <td class="text-end">{{ $entry ? number_format($entry->total, 2) : '0.00' }}</td>
                        <td class="text-end">{{ $entry ? number_format($entry->exempt, 2) : '0.00' }}</td>
                        <td class="text-end">{{ $entry ? number_format($entry->exonerated, 2) : '0.00' }}</td>
                        <td class="text-end">{{ $entry ? number_format($entry->non_subject, 2) : '0.00' }}</td>
                        <td class="text-end">{{ $entry ? number_format($entry->taxable, 2) : '0.00' }}</td>
                        <td class="text-end">{{ $entry ? number_format($entry->tax, 2) : '0.00' }}</td>
                        <td class="text-end">{{ $entry ? number_format($entry->withholding, 2) : '0.00' }}</td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="10" class="text-center text-muted">No hay registros para el año seleccionado.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot class="table-group-divider">
            @foreach(['entrada' => 'Total Compras', 'salida' => 'Total Ventas'] as $type => $label)
                <tr class="fw-bold">
                    <td colspan="2">{{ $label }}</td>
                    <td class="text-end">{{ $totals[$type]['ops'] }}</td>
                    <td class="text-end">{{ number_format($totals[$type]['total'], 2) }}</td>
                    <td class="text-end">{{ number_format($totals[$type]['exempt'], 2) }}</td>
                    <td class="text-end">{{ number_format($totals[$type]['exonerated'], 2) }}</td>
                    <td class="text-end">{{ number_format($totals[$type]['non_subject'], 2) }}</td>
                    <td class="text-end">{{ number_format($totals[$type]['taxable'], 2) }}</td>
                    <td class="text-end">{{ number_format($totals[$type]['tax'], 2) }}</td>
                    <td class="text-end">{{ number_format($totals[$type]['withholding'], 2) }}</td>
                </tr>
            @endforeach
            @php
                $netIva = $totals['salida']['tax'] - $totals['entrada']['tax'];
                $netWithholding = $totals['entrada']['withholding'] - $totals['salida']['withholding'];
            @endphp
            <tr class="table-warning fw-bold">
                <td colspan="8" class="text-end">Diferencia IVA a pagar (Ventas - Compras):</td>
                <td class="text-end" colspan="2">{{ number_format($netIva, 2) }}</td>
            </tr>
            <tr class="table-warning fw-bold">
                <td colspan="8" class="text-end">Diferencia IVA retenido (Compras - Ventas):</td>
                <td class="text-end" colspan="2">{{ number_format($netWithholding, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
@endsection
