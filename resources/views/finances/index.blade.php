@extends('layout')

@section('content')
    <h1>Finanzas: gastos y consumos</h1>
    <p class="text-muted">Registra gastos del negocio y consumos privados. Usa los filtros para analizar un período.</p>

    <form method="GET" action="{{ route('finances.index') }}" class="row g-3 mb-3" id="financesFilters">
        <div class="col-md-3">
            <label class="form-label">Desde</label>
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Hasta</label>
            <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Tipo</label>
            <select name="type" class="form-select">
                <option value="">Todos</option>
                <option value="business" {{ $type === 'business' ? 'selected' : '' }}>Gasto de negocio</option>
                <option value="personal" {{ $type === 'personal' ? 'selected' : '' }}>Consumo privado</option>
            </select>
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
        <div class="col-12 d-flex justify-content-between align-items-end">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <div class="d-flex gap-2">
                <a href="{{ route('finances.categories.index') }}" class="btn btn-outline-secondary">Categorías / Kategorien</a>
                <a href="{{ route('finances.create') }}" class="btn btn-success">Neuer Eintrag</a>
            </div>
        </div>
    </form>

    <div class="row mb-3" id="financesSummaryCards">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title mb-2">Gastos de negocio</h5>
                    <p class="h4 mb-0">{{ number_format($sumBusiness, 2) }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title mb-2">Consumos privados</h5>
                    <p class="h4 mb-0">{{ number_format($sumPersonal, 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive" id="financesTableWrapper">
        <table class="table table-striped table-sm align-middle" id="financesTable">
            <thead>
            <tr>
                <th>Fecha</th>
                <th>Tipo</th>
                <th>Categoría</th>
                <th class="text-end">Monto</th>
                <th>Pago</th>
                <th>Nota</th>
                <th style="width: 120px;"></th>
            </tr>
            </thead>
            <tbody>
            @forelse($expenses as $expense)
                <tr>
                    <td>{{ $expense->date->toDateString() }}</td>
                    <td>
                        @if($expense->type === 'business')
                            Gasto de negocio
                        @else
                            Consumo privado
                        @endif
                    </td>
                    <td>{{ $expense->category?->name }}</td>
                    <td class="text-end">{{ number_format($expense->amount, 2) }}</td>
                    <td>{{ $expense->payment_method }}</td>
                    <td>{{ $expense->note }}</td>
                    <td class="text-end">
                        <a href="{{ route('finances.edit', $expense) }}" class="btn btn-sm btn-primary">Editar</a>
                        <form action="{{ route('finances.destroy', $expense) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('¿Eliminar esta buchung?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">X</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted">Keine Buchungen im gewählten Zeitraum.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $expenses->links() }}
@endsection

@push('scripts')
    <script>
        window.GEME_TOUR_STEPS = [
            {
                intro: 'Bienvenido al módulo de finanzas. Aquí registras gastos del negocio y consumos personales.'
            },
            {
                element: '#financesFilters',
                intro: 'Filtra por fechas, tipo y categoría para analizar un periodo concreto.'
            },
            {
                element: '#financesSummaryCards',
                intro: 'Tarjetas resumen: compara rápidamente gastos del negocio y consumos privados.'
            },
            {
                element: '#financesTable',
                intro: 'Tabla de movimientos con detalles de monto, método de pago y notas. Usa las acciones para editar o eliminar.'
            },
            {
                element: '#financesTableWrapper .btn-success',
                intro: 'Crea un gasto nuevo desde aquí para mantener tu registro al día.'
            }
        ];
    </script>
@endpush
