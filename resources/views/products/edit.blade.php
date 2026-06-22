@extends('layout')

@section('content')
<h1>Editar producto</h1>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<form action="{{ route('products.update', $product) }}" method="POST" class="mt-3" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="mb-3">
        <label class="form-label">Categoría</label>
        <select name="category_id" class="form-select" required>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" {{ $product->category_id == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Nombre</label>
        <input type="text" name="name" class="form-control" value="{{ $product->name }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Código / SKU (opcional)</label>
        <input type="text" name="sku" class="form-control" value="{{ $product->sku }}" placeholder="Ej: HMB-CASA">
    </div>
    <div class="mb-3">
        <label class="form-label">Descripción</label>
        <textarea name="description" class="form-control">{{ $product->description }}</textarea>
    </div>
    <div class="mb-3">
        <label class="form-label">Imagen</label>
        @if($product->image_path)
            <div class="mb-2">
                <img src="{{ asset('storage/' . $product->image_path) }}" alt="Imagen actual" style="max-height: 80px;">
            </div>
        @endif
        <input type="file" name="image" class="form-control" accept="image/*">
        <div class="form-text">Opcional. Si subes una nueva imagen, reemplazará a la anterior.</div>
    </div>
    <div class="border rounded p-3 mb-3">
        <h5 class="mb-3">Precio &amp; Impuestos</h5>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Costo ($)</label>
                <input type="number" step="0.01" min="0" name="cost" id="cost" class="form-control" value="{{ old('cost', $product->cost) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Margen de ganancia (%)</label>
                <input type="number" step="0.01" min="0" name="markup_percent" id="markup_percent" class="form-control" value="{{ old('markup_percent', $product->markup_percent) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Precio de venta ($)</label>
                <input type="number" step="0.01" min="0" name="price" id="price" class="form-control" value="{{ old('price', $product->price) }}" required>
            </div>
        </div>
        <div class="form-check mt-3">
            <input class="form-check-input" type="checkbox" name="is_tax_inclusive" id="is_tax_inclusive" {{ old('is_tax_inclusive', $product->is_tax_inclusive) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_tax_inclusive">Precio incluye impuesto</label>
        </div>
        <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" name="is_service" id="is_service" {{ old('is_service', $product->is_service) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_service">Servicio (no usa stock)</label>
        </div>
        <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" name="is_price_change_allowed" id="is_price_change_allowed" {{ old('is_price_change_allowed', $product->is_price_change_allowed) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_price_change_allowed">Cambio de precio permitido</label>
        </div>
    </div>

    <div class="border rounded p-3 mb-3">
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
    <div class="border rounded p-3 mb-3">
        <h5 class="mb-3">Inventario y tipo de producto</h5>
        <div class="row">
            <div class="col-md-6">
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
            <div class="col-md-6">
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
    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" {{ $product->is_active ? 'checked' : '' }}>
        <label class="form-check-label" for="is_active">Activo</label>
    </div>
    <button type="submit" class="btn btn-primary">Actualizar</button>
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
            costInput.addEventListener('change', recalcPriceFromCostAndMargin);
            marginInput.addEventListener('change', recalcPriceFromCostAndMargin);
            priceInput.addEventListener('change', recalcMarginFromCostAndPrice);
        }
    })();
</script>
@endsection
