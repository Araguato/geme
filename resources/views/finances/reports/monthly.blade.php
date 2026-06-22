@extends('layout')

@section('content')
    <h1>Reporte mensual de gastos</h1>

    <form method="GET" action="{{ route('finances.reports.monthly') }}" class="row g-3 align-items-end mb-4">
        <div class="col-md-3">
            <label class="form-label">Año</label>
            <input type="number" name="year" class="form-control" min="2000" max="2100"
                   value="{{ $selectedYear }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Mes</label>
            <select name="month" class="form-select">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" @selected($m === $selectedMonth)>
                        {{ sprintf('%02d', $m) }}
                    </option>
                @endfor
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
            <tr>
                <th>Mes</th>
                <th class="text-end">Business</th>
                <th class="text-end">Privado</th>
                <th class="text-end">Total</th>
            </tr>
            </thead>
            <tbody>
            @foreach($periods as $p)
                @php($total = $p['business'] + $p['personal'])
                <tr @class(['fw-bold' => $loop->last])>
                    <td>{{ $p['label'] }}</td>
                    <td class="text-end">{{ number_format($p['business'], 2) }}</td>
                    <td class="text-end">{{ number_format($p['personal'], 2) }}</td>
                    <td class="text-end">{{ number_format($total, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
