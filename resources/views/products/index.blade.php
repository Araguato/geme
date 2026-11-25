@extends('layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3" id="productsHeader">
    <h1 id="productsTitle">{{ __('ui.products.title') }}</h1>
    <div class="btn-group" role="group" id="productsActions">
        <a href="{{ route('products.export.csv') }}" class="btn btn-outline-secondary">{{ __('ui.products.export_csv') }}</a>
        <a href="{{ route('products.import.form') }}" class="btn btn-outline-primary">{{ __('ui.products.import_csv') }}</a>
        <a href="{{ route('products.labels.bulk') }}" class="btn btn-outline-dark">
            <i class="bi bi-printer"></i> Etiquetas
        </a>
        <a href="{{ route('products.create') }}" class="btn btn-primary" id="productsCreateBtn">{{ __('ui.products.new_product') }}</a>
    </div>
</div>

<form method="GET" action="{{ route('products.index') }}" class="row g-2 align-items-end mb-3" id="productsFilters">
    <div class="col-md-4">
        <label class="form-label">{{ __('ui.products.filter_by_category') }}</label>
        <select name="category_id" class="form-select" onchange="this.form.submit()">
            <option value="">{{ __('ui.products.all_categories') }}</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" {{ (string)($selectedCategoryId ?? '') === (string)$category->id ? 'selected' : '' }}>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('ui.products.search_label') }}</label>
        <input type="text" name="search" class="form-control" value="{{ $search ?? '' }}" placeholder="{{ __('ui.products.search_placeholder') }}">
    </div>
    <div class="col-md-2 d-flex align-items-end">
        <button type="submit" class="btn btn-outline-secondary w-100">{{ __('ui.products.filter_button') }}</button>
    </div>
    <div class="col-md-2 d-flex align-items-end">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="sin_ubicacion" value="1" id="sinUbicacion" onchange="this.form.submit()" @checked($missingLocation)>
            <label class="form-check-label" for="sinUbicacion">
                Sin ubicación
                @if($missingLocationCount)<span class="badge rounded-pill bg-warning text-dark">{{ $missingLocationCount }}</span>@endif
            </label>
        </div>
    </div>
</form>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if(session('status'))
    <div class="alert alert-info">{{ session('status') }}</div>
@endif
<table class="table table-striped" id="productsTable">
    <thead>
    <tr>
        <th>Imagen</th>
        <th>Nombre</th>
        <th>Código</th>
        <th>Barcode</th>
        <th>Categoría</th>
        <th>Ubicación</th>
        <th>Precio</th>
        <th>Activo</th>
        <th>Acciones</th>
    </tr>
    </thead>
    <tbody>
    @foreach($products as $product)
        <tr>
            <td>
                @if($product->image_path)
                    <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->name }}" style="max-height: 50px;">
                @else
                    -
                @endif
            </td>
            <td>
                {{ $product->name }}
                @if($product->isParent())
                    <span class="badge bg-info ms-1" title="Tiene {{ $product->variants->count() }} variantes"><i class="bi bi-layers"></i> {{ $product->variants->count() }}</span>
                @endif
            </td>
            <td>{{ $product->sku }}</td>
            <td>{{ $product->barcodes?->first()?->barcode ?? '-' }}</td>
            <td>{{ $product->category?->name }}</td>
            <td>
                @php
                    $location = collect([$product->warehouse?->name, $product->location?->name, $product->aisle, $product->shelf, $product->rack, $product->bin, $product->section])->filter()->implode(' / ');
                @endphp
                @if($location)
                    {{ $location }}
                @elseif(!$product->is_service && !$product->is_prepared)
                    <span class="badge bg-warning text-dark" title="Sin ubicación asignada"><i class="bi bi-geo-alt"></i> Sin ubicación</span>
                @else
                    -
                @endif
            </td>
            <td>$ {{ number_format($product->price, 2) }}</td>
            <td>{{ $product->is_active ? 'Sí' : 'No' }}</td>
            <td>
                <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-secondary">Editar</a>
                <a href="{{ route('products.label', $product) }}" class="btn btn-sm btn-outline-dark" target="_blank" title="Imprimir etiqueta">
                    <i class="bi bi-printer"></i>
                </a>
                <form action="{{ route('products.destroy', $product) }}" method="POST" style="display:inline-block" onsubmit="return confirm('¿Eliminar producto?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                </form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

<script>
    // Búsqueda al escribir (sin pulsar Enter ni el botón): se envía el formulario tras una pausa corta
    (function () {
        const input = document.querySelector('input[name="search"]');
        if (!input || !input.form) return;
        let timer = null;
        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () { input.form.submit(); }, 600);
        });
        if (input.value) {
            input.focus();
            input.setSelectionRange(input.value.length, input.value.length);
        }
    })();
</script>
<script>
    window.GEME_TOUR_STEPS = [
        {
            intro: 'Aquí administras los productos del catálogo (precios, códigos, categoría y estado).'
        },
        {
            element: '#productsActions',
            intro: 'Acciones rápidas: exportar/importar CSV y crear un nuevo producto.'
        },
        {
            element: '#productsFilters',
            intro: 'Filtra el listado por categoría para ubicar productos más rápido.'
        },
        {
            element: '#productsTable',
            intro: 'Listado de productos. Puedes editar o eliminar desde aquí.'
        }
    ];
</script>
@endsection
