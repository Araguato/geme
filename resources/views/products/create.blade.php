@extends('layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Nuevo producto</h1>
    <button class="btn btn-sm btn-outline-secondary" onclick="startProductFormTour()">
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
<form action="{{ route('products.store') }}" method="POST" class="mt-3" enctype="multipart/form-data" id="productForm">
    @csrf
    <div class="mb-3" id="prod-category">
        <label class="form-label">Categoría</label>
        <select name="category_id" class="form-select" required>
            <option value="">Seleccione...</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3" id="prod-name">
        <label class="form-label">Nombre</label>
        <input type="text" name="name" class="form-control" required>
    </div>
    <div class="mb-3" id="prod-sku">
        <label class="form-label">Código / SKU (opcional)</label>
        <input type="text" name="sku" class="form-control" placeholder="Ej: HMB-CASA">
    </div>
    <div class="mb-3" id="prod-description">
        <label class="form-label">Descripción</label>
        <textarea name="description" class="form-control"></textarea>
    </div>
    <div class="mb-3" id="prod-description-zh">
        <label class="form-label">Descripción en chino simplificado (editable, se traduce automáticamente si se deja vacío)</label>
        <textarea name="description_zh" class="form-control"></textarea>
    </div>
    <div class="border rounded p-3 mb-3" id="prod-location">
        <h5 class="mb-3">Ubicación logística</h5>
        <div class="row g-3">
            <div class="col-md-6" id="prod-warehouse">
                <label class="form-label">Almacén / Bodega</label>
                <select name="warehouse_id" id="warehouse_id" class="form-select" onchange="filterProductLocations()">
                    <option value="">Sin asignar</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6" id="prod-location-select">
                <label class="form-label">Ubicación</label>
                <select name="location_id" id="location_id" class="form-select" onchange="showLocationDetails()">
                    <option value="">Sin asignar</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" data-warehouse="{{ $location->warehouse_id }}" data-aisle="{{ $location->aisle }}" data-shelf="{{ $location->shelf }}" data-rack="{{ $location->rack }}" data-bin="{{ $location->bin }}" data-section="{{ $location->section }}">
                            {{ $location->name }} @if($location->warehouse) ({{ $location->warehouse->name }}) @endif
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div id="location-details" class="mt-3 small text-muted" style="display: none;"></div>
    </div>
    <div class="mb-3" id="prod-image">
        <label class="form-label">Imagen principal</label>
        <input type="file" name="image" class="form-control" accept="image/*">
        <div class="form-text">Opcional. JPG, PNG o WEBP, máximo 5 MB.</div>
    </div>
    <div class="mb-3" id="prod-gallery">
        <label class="form-label">Imágenes adicionales (galería)</label>
        <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
        <div class="form-text">Puedes seleccionar varias imágenes. JPG, PNG o WEBP, máximo 5 MB cada una.</div>
    </div>
    <div class="border rounded p-3 mb-3" id="prod-pricing">
        <h5 class="mb-3">Precio &amp; Impuestos</h5>
        <div class="row g-3">
            <div class="col-md-4" id="prod-cost">
                <label class="form-label">Costo ($)</label>
                <input type="number" step="0.01" min="0" name="cost" id="cost" class="form-control" value="{{ old('cost') }}">
            </div>
            <div class="col-md-4" id="prod-margin">
                <label class="form-label">Margen de ganancia (%)</label>
                <input type="number" step="0.01" min="0" name="markup_percent" id="markup_percent" class="form-control" value="{{ old('markup_percent') }}">
            </div>
            <div class="col-md-4" id="prod-price">
                <label class="form-label">Precio de venta ($)</label>
                <input type="number" step="0.01" min="0" name="price" id="price" class="form-control" value="{{ old('price') }}" required>
            </div>
        </div>
        <div class="form-check mt-3" id="prod-tax">
            <input class="form-check-input" type="checkbox" name="is_tax_inclusive" id="is_tax_inclusive" {{ old('is_tax_inclusive', true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_tax_inclusive">Precio incluye impuesto</label>
        </div>
        <div class="form-check mt-2" id="prod-service">
            <input class="form-check-input" type="checkbox" name="is_service" id="is_service" {{ old('is_service') ? 'checked' : '' }}>
            <label class="form-check-label" for="is_service">Servicio (no usa stock)</label>
        </div>
        <div class="form-check mt-2" id="prod-price-change">
            <input class="form-check-input" type="checkbox" name="is_price_change_allowed" id="is_price_change_allowed" {{ old('is_price_change_allowed') ? 'checked' : '' }}>
            <label class="form-check-label" for="is_price_change_allowed">Cambio de precio permitido</label>
        </div>
    </div>

    <div class="border rounded p-3 mb-3" id="prod-barcodes">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">Barcodes (EAN/UPC)</h5>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="add-barcode-row">Agregar barcode</button>
        </div>
        <div class="table-responsive">
            <table class="table table-sm mb-0" id="barcodes-table">
                <thead>
                <tr>
                    <th style="width: 220px;">Barcode</th>
                    <th style="width: 220px;">Etiqueta (opcional)</th>
                    <th style="width: 160px;">Multiplicador</th>
                    <th style="width: 70px;"></th>
                </tr>
                </thead>
                <tbody>
                @php($rows = old('barcodes', [['barcode' => '', 'label' => '', 'multiplier' => 1]]))
                @foreach($rows as $i => $row)
                    <tr>
                        <td>
                            <input type="text" inputmode="numeric" name="barcodes[{{ $i }}][barcode]" class="form-control form-control-sm" value="{{ $row['barcode'] ?? '' }}" placeholder="EAN-13 / UPC">
                        </td>
                        <td>
                            <input type="text" name="barcodes[{{ $i }}][label]" class="form-control form-control-sm" value="{{ $row['label'] ?? '' }}" placeholder="unidad / caja">
                        </td>
                        <td>
                            <input type="number" step="0.001" min="0.001" name="barcodes[{{ $i }}][multiplier]" class="form-control form-control-sm" value="{{ $row['multiplier'] ?? 1 }}">
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-barcode-row">X</button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="form-text">Usa multiplicador 1 para unidad. Para caja, coloca por ejemplo 6, 12, 24, etc.</div>
    </div>
    <div class="border rounded p-3 mb-3" id="prod-inventory">
        <h5 class="mb-3">Inventario y tipo de producto</h5>
        <div class="row">
            <div class="col-md-6" id="prod-type-checks">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="is_stock_tracked" id="is_stock_tracked">
                    <label class="form-check-label" for="is_stock_tracked">Controla inventario</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="is_prepared" id="is_prepared">
                    <label class="form-check-label" for="is_prepared">Es producto preparado (tiene receta)</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="is_raw_material" id="is_raw_material">
                    <label class="form-check-label" for="is_raw_material">Es insumo / materia prima (no se vende directo)</label>
                </div>
            </div>
            <div class="col-md-6" id="prod-units">
                <div class="mb-2">
                    <label class="form-label" for="stock_unit_id">Unidad de inventario</label>
                    <select name="stock_unit_id" id="stock_unit_id" class="form-select">
                        <option value="">Sin unidad específica</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }}@if($unit->category) ({{ $unit->category }})@endif</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label" for="base_unit_id">Unidad base para recetas (opcional)</label>
                    <select name="base_unit_id" id="base_unit_id" class="form-select">
                        <option value="">Sin unidad específica</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }}@if($unit->category) ({{ $unit->category }})@endif</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label" for="default_unit">Unidad por defecto (texto libre, legado)</label>
                    <select name="default_unit" id="default_unit" class="form-select">
                        <option value="">Sin unidad específica</option>
                        <option value="unidad">Unidad</option>
                        <option value="kg">Kilogramo (kg)</option>
                        <option value="g">Gramo (g)</option>
                        <option value="l">Litro (l)</option>
                        <option value="ml">Mililitro (ml)</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="form-check mb-3" id="prod-active">
        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
        <label class="form-check-label" for="is_active">Activo</label>
    </div>
    <button type="submit" class="btn btn-primary">Guardar</button>
    <a href="{{ route('products.index') }}" class="btn btn-secondary">Cancelar</a>
</form>

<script>
    (function() {
        const table = document.getElementById('barcodes-table');
        const addBtn = document.getElementById('add-barcode-row');

        if (table && addBtn) {
            function renumber() {
                const rows = Array.from(table.querySelectorAll('tbody tr'));
                rows.forEach((tr, idx) => {
                    tr.querySelectorAll('input[name^="barcodes["]').forEach((input) => {
                        input.name = input.name.replace(/barcodes\[\d+\]/, 'barcodes[' + idx + ']');
                    });
                });
            }

            addBtn.addEventListener('click', function() {
                const tbody = table.querySelector('tbody');
                const idx = tbody.querySelectorAll('tr').length;
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><input type="text" inputmode="numeric" name="barcodes[${idx}][barcode]" class="form-control form-control-sm" placeholder="EAN-13 / UPC"></td>
                    <td><input type="text" name="barcodes[${idx}][label]" class="form-control form-control-sm" placeholder="unidad / caja"></td>
                    <td><input type="number" step="0.001" min="0.001" name="barcodes[${idx}][multiplier]" class="form-control form-control-sm" value="1"></td>
                    <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger remove-barcode-row">X</button></td>
                `;
                tbody.appendChild(tr);
            });

            table.addEventListener('click', function(e) {
                const btn = e.target.closest('.remove-barcode-row');
                if (!btn) return;
                const tr = btn.closest('tr');
                if (tr) tr.remove();
                renumber();
            });
        }

        // Reglas de costo / margen / precio
        const costInput = document.getElementById('cost');
        const marginInput = document.getElementById('markup_percent');
        const priceInput = document.getElementById('price');

        function parseNumber(input) {
            const v = parseFloat(input.value.replace(',', '.'));
            return isNaN(v) ? null : v;
        }

        function recalcPriceFromCostAndMargin() {
            if (!costInput || !marginInput || !priceInput) return;
            const cost = parseNumber(costInput);
            const margin = parseNumber(marginInput);
            if (cost === null || margin === null) return;
            if (cost < 0 || margin < 0) return;
            const price = cost * (1 + margin / 100);
            priceInput.value = price.toFixed(2);
        }

        function recalcMarginFromCostAndPrice() {
            if (!costInput || !marginInput || !priceInput) return;
            const cost = parseNumber(costInput);
            const price = parseNumber(priceInput);
            if (cost === null || price === null) return;
            if (cost <= 0) return;
            const margin = (price / cost - 1) * 100;
            marginInput.value = margin.toFixed(2);
        }

        if (costInput && marginInput && priceInput) {
            costInput.addEventListener('input', recalcPriceFromCostAndMargin);
            marginInput.addEventListener('input', recalcPriceFromCostAndMargin);
            priceInput.addEventListener('input', recalcMarginFromCostAndPrice);
        }

        function filterProductLocations() {
            const warehouseId = document.getElementById('warehouse_id').value;
            const select = document.getElementById('location_id');
            Array.from(select.options).forEach(option => {
                if (!option.value) return;
                option.style.display = !warehouseId || option.dataset.warehouse === warehouseId ? 'block' : 'none';
            });
            if (select.options[select.selectedIndex].style.display === 'none') {
                select.value = '';
                showLocationDetails();
            }
        }

        function showLocationDetails() {
            const select = document.getElementById('location_id');
            const option = select.options[select.selectedIndex];
            const container = document.getElementById('location-details');
            if (!option.value) {
                container.style.display = 'none';
                container.textContent = '';
                return;
            }
            const parts = [];
            if (option.dataset.aisle) parts.push('Pasillo: ' + option.dataset.aisle);
            if (option.dataset.shelf) parts.push('Estante: ' + option.dataset.shelf);
            if (option.dataset.rack) parts.push('Anaquel: ' + option.dataset.rack);
            if (option.dataset.bin) parts.push('Cajón: ' + option.dataset.bin);
            if (option.dataset.section) parts.push('Vitrina/Sección: ' + option.dataset.section);
            container.textContent = parts.join(' | ') || 'Sin detalles';
            container.style.display = 'block';
        }

        filterProductLocations();
    })();

    function startProductFormTour() {
        if (typeof introJs === 'undefined') return;
        introJs()
            .setOptions({
                steps: [
                    { element: '#prod-category', intro: 'Selecciona la categoría del producto. Antes debes crear categorías desde el menú correspondiente.' },
                    { element: '#prod-name', intro: 'Nombre del producto como lo verán en el catálogo y el TPV.' },
                    { element: '#prod-sku', intro: 'Código interno o SKU. Es opcional pero útil para reportes y etiquetas.' },
                    { element: '#prod-description', intro: 'Descripción del producto. Se traducirá automáticamente al chino si dejas vacío el campo de abajo.' },
                    { element: '#prod-description-zh', intro: 'Descripción en chino. Si la dejas vacía, se genera automáticamente desde la descripción principal.' },
                    { element: '#prod-location', intro: 'Aquí asignas el depósito y la ubicación exacta del producto. La ubicación se filtra según el depósito seleccionado.' },
                    { element: '#prod-image', intro: 'Opcional: sube una imagen del producto para el catálogo público.' },
                    { element: '#prod-pricing', intro: 'Define costo, margen y precio de venta. Si pones costo y margen, el precio se calcula automáticamente.' },
                    { element: '#prod-barcodes', intro: 'Agrega códigos de barras para escanear en el TPV. El multiplicador sirve para empaques (ej: caja de 6 unidades).' },
                    { element: '#prod-inventory', intro: 'Indica si el producto controla stock, si es preparado o materia prima, y selecciona sus unidades.' },
                    { element: '#prod-active', intro: 'Desactiva productos que ya no vendes. No se eliminan, solo dejan de aparecer en el TPV y catálogo.' }
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
