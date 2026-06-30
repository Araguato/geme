@extends('layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Editar producto</h1>
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
<form action="{{ route('products.update', $product) }}" method="POST" class="mt-3" enctype="multipart/form-data" id="productForm">
    @csrf
    @method('PUT')
    <div class="mb-3" id="prod-category">
        <label class="form-label">Categoría</label>
        <select name="category_id" class="form-select" required>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" {{ $product->category_id == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3" id="prod-name">
        <label class="form-label">Nombre</label>
        <input type="text" name="name" class="form-control" value="{{ $product->name }}" required>
    </div>
    <div class="mb-3" id="prod-sku">
        <label class="form-label">Código / SKU (opcional)</label>
        <input type="text" name="sku" class="form-control" value="{{ $product->sku }}" placeholder="Ej: HMB-CASA">
    </div>
    <div class="mb-3" id="prod-description">
        <label class="form-label">Descripción</label>
        <textarea name="description" class="form-control">{{ $product->description }}</textarea>
    </div>
    <div class="mb-3" id="prod-description-zh">
        <label class="form-label">Descripción en chino simplificado (editable, se traduce automáticamente si se deja vacío)</label>
        <textarea name="description_zh" class="form-control">{{ $product->description_zh }}</textarea>
    </div>
    <div class="border rounded p-3 mb-3" id="prod-location">
        <h5 class="mb-3">Ubicación logística</h5>
        <div class="row g-3">
            <div class="col-md-6" id="prod-warehouse">
                <label class="form-label">Almacén / Bodega</label>
                <select name="warehouse_id" id="warehouse_id" class="form-select" onchange="filterProductLocations()">
                    <option value="" {{ $product->warehouse_id ? '' : 'selected' }}>Sin asignar</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ $product->warehouse_id == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6" id="prod-location-select">
                <label class="form-label">Ubicación</label>
                <select name="location_id" id="location_id" class="form-select" onchange="showLocationDetails()">
                    <option value="" {{ $product->location_id ? '' : 'selected' }}>Sin asignar</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" data-warehouse="{{ $location->warehouse_id }}" data-aisle="{{ $location->aisle }}" data-shelf="{{ $location->shelf }}" data-rack="{{ $location->rack }}" data-bin="{{ $location->bin }}" data-section="{{ $location->section }}" {{ $product->location_id == $location->id ? 'selected' : '' }}>
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
        <div class="form-text">Opcional. Si subes una imagen, se agregará a la galería y se marcará como principal.</div>
    </div>
    <div class="mb-3" id="prod-gallery">
        <label class="form-label">Imágenes adicionales (galería)</label>
        <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
        <div class="form-text">Puedes seleccionar varias imágenes. JPG, PNG o WEBP, máximo 5 MB cada una.</div>
    </div>

    @if($product->images->count() > 0)
        <div class="mb-3 border rounded p-3">
            <label class="form-label d-block">Galería actual</label>
            <div class="row g-3">
                @foreach($product->images as $image)
                    <div class="col-md-3 col-sm-4">
                        <div class="card h-100">
                            <img src="{{ asset('storage/' . $image->path) }}" class="card-img-top" style="height: 120px; object-fit: cover;" alt="">
                            <div class="card-body p-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="main_image_id" id="main_{{ $image->id }}" value="{{ $image->id }}" {{ $image->is_main ? 'checked' : '' }}>
                                    <label class="form-check-label" for="main_{{ $image->id }}">Principal</label>
                                </div>
                                <div class="form-check mt-1">
                                    <input class="form-check-input" type="checkbox" name="delete_images[]" id="delete_{{ $image->id }}" value="{{ $image->id }}">
                                    <label class="form-check-label text-danger" for="delete_{{ $image->id }}">Eliminar</label>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
    <div class="border rounded p-3 mb-3" id="prod-pricing">
        <h5 class="mb-3">Precio &amp; Impuestos</h5>
        <div class="row g-3">
            <div class="col-md-4" id="prod-cost">
                <label class="form-label">Costo ($)</label>
                <input type="number" step="0.01" min="0" name="cost" id="cost" class="form-control" value="{{ old('cost', $product->cost) }}">
            </div>
            <div class="col-md-4" id="prod-margin">
                <label class="form-label">Margen de ganancia (%)</label>
                <input type="number" step="0.01" min="0" name="markup_percent" id="markup_percent" class="form-control" value="{{ old('markup_percent', $product->markup_percent) }}">
            </div>
            <div class="col-md-4" id="prod-price">
                <label class="form-label">Precio de venta ($)</label>
                <input type="number" step="0.01" min="0" name="price" id="price" class="form-control" value="{{ old('price', $product->price) }}" required>
            </div>
        </div>
        <div class="form-check mt-3" id="prod-tax">
            <input class="form-check-input" type="checkbox" name="is_tax_inclusive" id="is_tax_inclusive" {{ old('is_tax_inclusive', $product->is_tax_inclusive) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_tax_inclusive">Precio incluye impuesto</label>
        </div>
        <div class="form-check mt-2" id="prod-service">
            <input class="form-check-input" type="checkbox" name="is_service" id="is_service" {{ old('is_service', $product->is_service) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_service">Servicio (no usa stock)</label>
        </div>
        <div class="form-check mt-2" id="prod-price-change">
            <input class="form-check-input" type="checkbox" name="is_price_change_allowed" id="is_price_change_allowed" {{ old('is_price_change_allowed', $product->is_price_change_allowed) ? 'checked' : '' }}>
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
                @php($rows = old('barcodes', ($product->barcodes ?? collect())->map(fn($b) => ['barcode' => $b->barcode, 'label' => $b->label, 'multiplier' => $b->multiplier])->values()->all()))
                @php($rows = count($rows) ? $rows : [['barcode' => '', 'label' => '', 'multiplier' => 1]])
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
                    <input class="form-check-input" type="checkbox" name="is_stock_tracked" id="is_stock_tracked" {{ $product->is_stock_tracked ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_stock_tracked">Controla inventario</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="is_prepared" id="is_prepared" {{ $product->is_prepared ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_prepared">Es producto preparado (tiene receta)</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="is_raw_material" id="is_raw_material" {{ $product->is_raw_material ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_raw_material">Es insumo / materia prima (no se vende directo)</label>
                </div>
            </div>
            <div class="col-md-6" id="prod-units">
                <div class="mb-2">
                    <label class="form-label" for="default_unit">Unidad por defecto</label>
                    <select name="default_unit" id="default_unit" class="form-select">
                        <option value="" {{ $product->default_unit ? '' : 'selected' }}>Sin unidad específica</option>
                        <option value="unidad" {{ $product->default_unit === 'unidad' ? 'selected' : '' }}>Unidad</option>
                        <option value="kg" {{ $product->default_unit === 'kg' ? 'selected' : '' }}>Kilogramo (kg)</option>
                        <option value="g" {{ $product->default_unit === 'g' ? 'selected' : '' }}>Gramo (g)</option>
                        <option value="l" {{ $product->default_unit === 'l' ? 'selected' : '' }}>Litro (l)</option>
                        <option value="ml" {{ $product->default_unit === 'ml' ? 'selected' : '' }}>Mililitro (ml)</option>
                    </select>
                </div>
                @if($product->is_prepared)
                    <div class="mt-3">
                        <a href="{{ route('products.recipe.edit', $product) }}" class="btn btn-sm btn-outline-light">
                            Definir / editar receta
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="form-check mb-3" id="prod-active">
        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" {{ $product->is_active ? 'checked' : '' }}>
        <label class="form-check-label" for="is_active">Activo</label>
    </div>
    <button type="submit" class="btn btn-primary">Actualizar</button>
    <a href="{{ route('products.index') }}" class="btn btn-secondary">Cancelar</a>
    <a href="{{ route('products.label', $product) }}" target="_blank" class="btn btn-outline-info">Imprimir etiqueta</a>
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
            costInput.addEventListener('change', recalcPriceFromCostAndMargin);
            marginInput.addEventListener('change', recalcPriceFromCostAndMargin);
            priceInput.addEventListener('change', recalcMarginFromCostAndPrice);
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
        showLocationDetails();
    })();

    function startProductFormTour() {
        if (typeof introJs === 'undefined') return;
        introJs()
            .setOptions({
                steps: [
                    { element: '#prod-category', intro: 'Selecciona la categoría del producto.' },
                    { element: '#prod-name', intro: 'Nombre del producto como lo verán en el catálogo y el TPV.' },
                    { element: '#prod-sku', intro: 'Código interno o SKU. Es opcional pero útil para reportes y etiquetas.' },
                    { element: '#prod-description', intro: 'Descripción del producto. Se traducirá automáticamente al chino si dejas vacío el campo de abajo.' },
                    { element: '#prod-description-zh', intro: 'Descripción en chino. Si la dejas vacía, se genera automáticamente desde la descripción principal.' },
                    { element: '#prod-location', intro: 'Aquí asignas el depósito y la ubicación exacta del producto. La ubicación se filtra según el depósito seleccionado.' },
                    { element: '#prod-image', intro: 'Opcional: sube o reemplaza la imagen del producto para el catálogo público.' },
                    { element: '#prod-pricing', intro: 'Define costo, margen y precio de venta. Si pones costo y margen, el precio se calcula automáticamente.' },
                    { element: '#prod-barcodes', intro: 'Agrega o edita códigos de barras para escanear en el TPV. El multiplicador sirve para empaques.' },
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
