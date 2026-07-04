@extends('layout')

@section('title', 'Actualizar precios')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Actualizar precios en general</h1>
        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">Volver a productos</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">Ajuste masivo</div>
                <div class="card-body">
                    <form action="{{ route('products.prices.update') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="percentage" class="form-label">Porcentaje de ajuste</label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="percentage" id="percentage" class="form-control" placeholder="Ej: 10" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <div class="form-text">Use valores positivos para subir y negativos para bajar.</div>
                        </div>

                        <div class="mb-3">
                            <label for="category_id" class="form-label">Categoría (opcional)</label>
                            <select name="category_id" id="category_id" class="form-select">
                                <option value="">Todas las categorías</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ $categoryId == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Si selecciona una, el ajuste solo afectará a esa categoría.</div>
                        </div>

                        <div class="mb-3">
                            <label for="round_to" class="form-label">Redondear a (opcional)</label>
                            <select name="round_to" id="round_to" class="form-select">
                                <option value="">Sin redondeo</option>
                                <option value="0.01">0.01</option>
                                <option value="0.05">0.05</option>
                                <option value="0.10">0.10</option>
                                <option value="0.25">0.25</option>
                                <option value="0.50">0.50</option>
                                <option value="1.00">1.00</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Aplicar ajuste</button>
                    </form>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">Resumen afectado</div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Productos activos</span>
                            <span class="fw-semibold">{{ number_format($stats->count ?? 0) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Precio mínimo</span>
                            <span class="fw-semibold">$ {{ number_format($stats->min_price ?? 0, 2) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Precio máximo</span>
                            <span class="fw-semibold">$ {{ number_format($stats->max_price ?? 0, 2) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Precio promedio</span>
                            <span class="fw-semibold">$ {{ number_format($stats->avg_price ?? 0, 2) }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Vista previa (primeros {{ $products->count() }} productos)</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>SKU</th>
                                    <th class="text-end">Precio actual</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($products as $product)
                                    <tr>
                                        <td>{{ $product->name }}</td>
                                        <td>{{ $product->sku ?? '—' }}</td>
                                        <td class="text-end">$ {{ number_format($product->price, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-body-secondary">No hay productos activos para mostrar.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
