@extends('layout')

@section('content')
<h1>Configuración de tasa BCV</h1>
<p class="text-muted">Solo administrador: introduce la tasa oficial del día (Bs por 1 USD) tomada del BCV.</p>

<form action="{{ route('settings.bcv.update') }}" method="POST" class="mt-3" style="max-width: 400px;">
    @csrf
    <div class="mb-3">
        <label class="form-label">Tasa BCV (Bs / USD)</label>
        <input type="number" step="0.0001" name="bcv_rate" class="form-control" value="{{ old('bcv_rate', $bcvRate) }}" required>
    </div>
    <button type="submit" class="btn btn-primary">Guardar tasa</button>
</form>

@if(isset($rates) && $rates->count() > 0)
    <hr class="my-4">
    <h2 class="h5 mb-3">Histórico (últimos {{ $rates->count() }} días)</h2>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-body">
                    <canvas id="bcvChart" height="120"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-sm mb-0">
                            <thead>
                            <tr>
                                <th>Fecha</th>
                                <th class="text-end">Bs / USD</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($rates->reverse() as $row)
                                <tr>
                                    <td>{{ $row->rate_date }}</td>
                                    <td class="text-end">{{ number_format((float) $row->bs_per_usd, 4) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const bcvLabels = @json($rates->map(fn($r) => (string) $r->rate_date));
        const bcvValues = @json($rates->map(fn($r) => (float) $r->bs_per_usd));

        const ctx = document.getElementById('bcvChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: bcvLabels,
                    datasets: [{
                        label: 'Tasa BCV (Bs / USD)',
                        data: bcvValues,
                        borderWidth: 2,
                        tension: 0.25,
                        pointRadius: 2,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: true },
                    },
                    scales: {
                        y: { beginAtZero: false }
                    }
                }
            });
        }
    </script>
@endif
@endsection
