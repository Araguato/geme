@extends('layout')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Ajuste de inventario</h1>
        <button class="btn btn-sm btn-outline-secondary" onclick="startAdjustFormTour()">
            <i class="bi bi-question-circle"></i> Tour del formulario
        </button>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('stock.adjust') }}" method="POST" class="mt-3" id="adjustForm">
        @csrf

        <div class="mb-3" id="adj-product">
            <label class="form-label">Producto</label>
            <input type="hidden" name="product_id" id="product_id" value="{{ optional($selectedProduct)->id }}">

            @if($selectedProduct)
                <div class="d-flex align-items-center gap-3 border rounded p-2 mb-2" id="selected-product-card">
                    @if($selectedProduct->mainImage)
                        <img src="{{ asset('storage/' . $selectedProduct->mainImage->path) }}" alt="{{ $selectedProduct->name }}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                    @else
                        <div class="bg-secondary-subtle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; border-radius: 4px;">
                            <i class="bi bi-image text-secondary fs-4"></i>
                        </div>
                    @endif
                    <div class="flex-grow-1">
                        <div class="fw-semibold">{{ $selectedProduct->name }}</div>
                        <div class="small text-muted">
                            @if($selectedProduct->sku) SKU: {{ $selectedProduct->sku }} @endif
                            @if($selectedProduct->sku && $selectedProduct->category) &middot; @endif
                            {{ $selectedProduct->category?->name ?? '' }}
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearProductSelection()">Cambiar</button>
                </div>
            @endif

            <div id="product-search-wrapper" style="{{ $selectedProduct ? 'display: none;' : '' }}">
                <div class="position-relative">
                    <input type="text"
                           class="form-control"
                           id="product-search-input"
                           placeholder="Buscar producto por nombre, SKU o código de barras..."
                           autocomplete="off">
                    <div id="product-search-results"
                         class="list-group position-absolute w-100 shadow-sm"
                         style="z-index: 1050; max-height: 320px; overflow-y: auto; display: none;">
                    </div>
                </div>
            </div>
        </div>

        @if($selectedProduct)
            <div class="alert alert-info py-2 mb-3" id="adj-stock-info">
                @php
                    $qty = optional($selectedStockItem)->quantity ?? 0;
                    $textUnit = optional($selectedStockItem)->unit ?: ($selectedProduct->default_unit ?: '');
                    $avg = optional($selectedStockItem)->average_cost ?? 0;
                @endphp
                <strong>Stock actual de {{ $selectedProduct->name }}:</strong>
                {{ number_format($qty, 3) }} {{ $textUnit }}
                @if($textUnit)
                    &​mdash;
                    Costo promedio: $ {{ number_format($avg, 4) }} por {{ $textUnit }}
                @endif
            </div>
        @endif

        <div class="row g-3 mb-3" id="adj-type-reason">
            <div class="col-md-4" id="adj-type">
                <label class="form-label">Tipo de movimiento</label>
                <select name="type" id="type" class="form-select" required onchange="toggleTransfer()">
                    <option value="in" {{ old('type') === 'in' ? 'selected' : '' }}>Entrada (compra, carga inicial)</option>
                    <option value="out" {{ old('type') === 'out' ? 'selected' : '' }}>Salida (consumo, merma)</option>
                    <option value="adjustment" {{ old('type') === 'adjustment' ? 'selected' : '' }}>Ajuste manual</option>
                    <option value="transfer" {{ old('type') === 'transfer' ? 'selected' : '' }}>Transferencia entre depósitos</option>
                </select>
            </div>
            <div class="col-md-4" id="adj-reason">
                <label class="form-label">Motivo</label>
                <input type="text"
                       name="reason"
                       class="form-control"
                       value="{{ old('reason', 'Compra / entrada') }}"
                       maxlength="50"
                       required>
            </div>
        </div>

        <div class="row g-3 mb-3" id="adj-origin">
            <div class="col-md-4" id="adj-warehouse">
                <label class="form-label">Depósito</label>
                <select name="warehouse_id" id="warehouse_id" class="form-select" onchange="filterLocations()">
                    <option value="">Sin depósito</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ old('warehouse_id', optional($selectedProduct)->warehouse_id) == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4" id="adj-location">
                <label class="form-label">Ubicación</label>
                <select name="location_id" id="location_id" class="form-select" onchange="fillLocationDetails()">
                    <option value="">Sin ubicación</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}"
                                data-warehouse="{{ $location->warehouse_id }}"
                                data-aisle="{{ $location->aisle }}"
                                data-shelf="{{ $location->shelf }}"
                                data-rack="{{ $location->rack }}"
                                data-bin="{{ $location->bin }}"
                                data-section="{{ $location->section }}"
                                {{ old('location_id', optional($selectedProduct)->location_id) == $location->id ? 'selected' : '' }}>
                            {{ $location->name }}@if($location->warehouse) ({{ $location->warehouse->name }})@endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4" id="to-warehouse-wrapper" style="display: none;">
                <label class="form-label">Depósito destino</label>
                <select name="to_warehouse_id" id="to_warehouse_id" class="form-select" onchange="filterToLocations()">
                    <option value="">Seleccione...</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ old('to_warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4" id="to-location-wrapper" style="display: none;">
                <label class="form-label">Ubicación destino</label>
                <select name="to_location_id" id="to_location_id" class="form-select">
                    <option value="">Seleccione...</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" data-warehouse="{{ $location->warehouse_id }}" {{ old('to_location_id') == $location->id ? 'selected' : '' }}>
                            {{ $location->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="row g-3 mb-3" id="adj-details">
            <div class="col-md-2">
                <label class="form-label">Pasillo</label>
                <input type="text" name="aisle" id="aisle" class="form-control" value="{{ old('aisle', optional($selectedProduct)->aisle) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Estante</label>
                <input type="text" name="shelf" id="shelf" class="form-control" value="{{ old('shelf', optional($selectedProduct)->shelf) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Anaquel</label>
                <input type="text" name="rack" id="rack" class="form-control" value="{{ old('rack', optional($selectedProduct)->rack) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Cajón</label>
                <input type="text" name="bin" id="bin" class="form-control" value="{{ old('bin', optional($selectedProduct)->bin) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Vitrina/Cubículo</label>
                <input type="text" name="section" id="section" class="form-control" value="{{ old('section', optional($selectedProduct)->section) }}">
            </div>
        </div>

        <div class="row g-3 mb-3" id="adj-qty">
            <div class="col-md-3">
                <label class="form-label">Cantidad</label>
                <input type="number"
                       step="any"
                       min="0.0001"
                       name="quantity"
                       class="form-control"
                       value="{{ old('quantity') }}"
                       required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Unidad</label>
                <select name="unit_id" class="form-select">
                    <option value="">Seleccione unidad...</option>
                    @php
                        $selectedUnitId = old('unit_id')
                            ?? optional($selectedStockItem)->unit_id
                            ?? optional($selectedProduct)->stock_unit_id;
                    @endphp
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}" {{ (int) $selectedUnitId === $unit->id ? 'selected' : '' }}>
                            {{ $unit->name }}@if($unit->category) ({{ $unit->category }})@endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Costo unitario ($) <small>(solo entradas)</small></label>
                <input type="number"
                       step="0.0001"
                       min="0"
                       name="unit_cost"
                       class="form-control"
                       value="{{ old('unit_cost') }}">
                <div class="form-text">
                    Si se deja vacío en una entrada, se usará el costo promedio actual.
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Guardar ajuste</button>
        <a href="{{ route('stock.index') }}" class="btn btn-secondary">Cancelar</a>
    </form>

    <script>
        window.GEME_TOUR_STEPS = [
            { element: '#adj-product', intro: 'Selecciona el producto al que le vas a mover el stock. Si eliges uno, la página recarga para mostrar su stock actual.' },
            { element: '#adj-stock-info', intro: 'Aquí ves el stock actual y el costo promedio del producto seleccionado.' },
            { element: '#adj-type', intro: 'Elige el tipo de movimiento: entrada, salida, ajuste manual o transferencia entre depósitos.' },
            { element: '#adj-reason', intro: 'Describe el motivo del movimiento. Ej: Compra, merma, corrección de inventario.' },
            { element: '#adj-warehouse', intro: 'Selecciona el depósito origen del movimiento.' },
            { element: '#adj-location', intro: 'Selecciona la ubicación exacta dentro del depósito. Se filtra automáticamente por depósito y rellena los detalles.' },
            { element: '#to-warehouse-wrapper', intro: 'En transferencias, indica el depósito destino.' },
            { element: '#to-location-wrapper', intro: 'En transferencias, indica la ubicación destino.' },
            { element: '#adj-details', intro: 'Estos campos se rellenan automáticamente al elegir una ubicación. Puedes editarlos manualmente si es necesario.' },
            { element: '#adj-qty', intro: 'Indica la cantidad y unidad del movimiento. En entradas, también puedes registrar el costo unitario.' }
        ];

        // Buscador de productos con imágenes
        const productSearchInput = document.getElementById('product-search-input');
        const productSearchResults = document.getElementById('product-search-results');
        let searchTimeout;

        if (productSearchInput) {
            productSearchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const q = this.value.trim();
                if (q.length < 2) {
                    productSearchResults.style.display = 'none';
                    return;
                }
                searchTimeout = setTimeout(() => {
                    fetch(`{{ route('products.api-search') }}?q=${encodeURIComponent(q)}`)
                        .then(r => r.json())
                        .then(data => {
                            productSearchResults.innerHTML = '';
                            if (data.products.length === 0) {
                                productSearchResults.innerHTML = '<div class="list-group-item text-muted">No se encontraron productos</div>';
                                productSearchResults.style.display = 'block';
                                return;
                            }
                            data.products.forEach(p => {
                                const imgHtml = p.image_url
                                    ? `<img src="${p.image_url}" alt="${p.name}" style="width: 48px; height: 48px; object-fit: cover; border-radius: 4px; flex-shrink: 0;">`
                                    : `<div class="bg-secondary-subtle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; border-radius: 4px; flex-shrink: 0;"><i class="bi bi-image text-secondary"></i></div>`;
                                const variantLabel = p.variant_label ? `<span class="badge bg-secondary ms-1">${p.variant_label}</span>` : '';
                                const sku = p.sku ? `<div class="small text-muted">SKU: ${p.sku}</div>` : '';
                                const item = document.createElement('a');
                                item.href = '#';
                                item.className = 'list-group-item list-group-item-action d-flex align-items-center gap-2 py-2';
                                item.innerHTML = `${imgHtml}<div class="flex-grow-1 min-width-0"><div class="fw-semibold text-truncate">${p.name}${variantLabel}</div>${sku}<div class="small text-success">$ ${parseFloat(p.price).toFixed(2)}</div></div>`;
                                item.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    window.location.href = `{{ route('stock.adjust.form') }}?product_id=${p.id}`;
                                });
                                productSearchResults.appendChild(item);
                            });
                            productSearchResults.style.display = 'block';
                        });
                }, 300);
            });

            document.addEventListener('click', function(e) {
                if (!productSearchInput.contains(e.target) && !productSearchResults.contains(e.target)) {
                    productSearchResults.style.display = 'none';
                }
            });
        }

        function clearProductSelection() {
            const card = document.getElementById('selected-product-card');
            if (card) card.style.display = 'none';
            document.getElementById('product-search-wrapper').style.display = 'block';
            document.getElementById('product_id').value = '';
            if (productSearchInput) productSearchInput.focus();
        }

        function toggleTransfer() {
            const type = document.getElementById('type').value;
            document.getElementById('to-warehouse-wrapper').style.display = type === 'transfer' ? 'block' : 'none';
            document.getElementById('to-location-wrapper').style.display = type === 'transfer' ? 'block' : 'none';
        }

        function filterLocations() {
            const warehouseId = document.getElementById('warehouse_id').value;
            const select = document.getElementById('location_id');
            Array.from(select.options).forEach(option => {
                if (!option.value) return;
                option.style.display = !warehouseId || option.dataset.warehouse === warehouseId ? 'block' : 'none';
            });
            if (select.options[select.selectedIndex].style.display === 'none') {
                select.value = '';
            }
        }

        function filterToLocations() {
            const warehouseId = document.getElementById('to_warehouse_id').value;
            const select = document.getElementById('to_location_id');
            Array.from(select.options).forEach(option => {
                if (!option.value) return;
                option.style.display = !warehouseId || option.dataset.warehouse === warehouseId ? 'block' : 'none';
            });
            if (select.options[select.selectedIndex].style.display === 'none') {
                select.value = '';
            }
        }

        function fillLocationDetails() {
            const select = document.getElementById('location_id');
            const option = select.options[select.selectedIndex];
            if (!option.value) return;
            document.getElementById('aisle').value = option.dataset.aisle || '';
            document.getElementById('shelf').value = option.dataset.shelf || '';
            document.getElementById('rack').value = option.dataset.rack || '';
            document.getElementById('bin').value = option.dataset.bin || '';
            document.getElementById('section').value = option.dataset.section || '';
        }

        toggleTransfer();
        filterLocations();
        filterToLocations();

        function startAdjustFormTour() {
            if (typeof introJs === 'undefined') return;
            introJs()
                .setOptions({
                    steps: [
                        { element: '#adj-product', intro: 'Selecciona el producto al que le vas a mover el stock. Si eliges uno, la página recarga para mostrar su stock actual.' },
                        { element: '#adj-stock-info', intro: 'Aquí ves el stock actual y el costo promedio del producto seleccionado.' },
                        { element: '#adj-type', intro: 'Elige el tipo de movimiento: entrada, salida, ajuste manual o transferencia entre depósitos.' },
                        { element: '#adj-reason', intro: 'Describe el motivo del movimiento. Ej: Compra, merma, corrección de inventario.' },
                        { element: '#adj-warehouse', intro: 'Selecciona el depósito origen del movimiento.' },
                        { element: '#adj-location', intro: 'Selecciona la ubicación exacta dentro del depósito. Se filtra automáticamente por depósito y rellena los detalles.' },
                        { element: '#to-warehouse-wrapper', intro: 'En transferencias, indica el depósito destino.' },
                        { element: '#to-location-wrapper', intro: 'En transferencias, indica la ubicación destino.' },
                        { element: '#adj-details', intro: 'Estos campos se rellenan automáticamente al elegir una ubicación. Puedes editarlos manualmente si es necesario.' },
                        { element: '#adj-qty', intro: 'Indica la cantidad y unidad del movimiento. En entradas, también puedes registrar el costo unitario.' }
                    ],
                    nextLabel: 'Siguiente',
                    prevLabel: 'Anterior',
                    skipLabel: 'Saltar',
                    doneLabel: 'Listo',
                    showProgress: true,
                    showBullets: true,
                })
                .start();
        }
    </script>
@endsection