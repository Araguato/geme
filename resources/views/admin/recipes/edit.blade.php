@extends('layout')

@section('content')
    <h1>Receta para: {{ $product->name }}</h1>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('products.recipe.update', $product) }}" method="POST" class="mt-3">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">Rendimiento de la receta</label>
            <div class="row g-2">
                <div class="col-md-4">
                    <input type="number" step="0.001" min="0.001" name="yield_quantity" class="form-control"
                           value="{{ old('yield_quantity', $recipe->yield_quantity) }}" required>
                </div>
                <div class="col-md-4">
                    <input type="text" name="yield_unit" class="form-control"
                           value="{{ old('yield_unit', $recipe->yield_unit) }}" placeholder="Porciones, unidades, etc.">
                </div>
            </div>
            <div class="form-text">Ejemplo: 1 olla de arroz rinde 10 porciones; aquí indicas cuántas porciones produce esta receta.</div>
        </div>

        <h5 class="mt-4">Insumos de la receta</h5>
        <div class="table-responsive mb-3">
            <table class="table table-striped align-middle">
                <thead>
                <tr>
                    <th>Insumo / componente</th>
                    <th>Cantidad por lote</th>
                    <th>Unidad</th>
                    <th>Merma %</th>
                    <th></th>
                </tr>
                </thead>
                <tbody id="recipe-items-body">
                @php($oldItems = old('items', $recipe->items->toArray()))
                @forelse($oldItems as $index => $item)
                    <tr>
                        <td>
                            <select name="items[{{ $index }}][component_product_id]" class="form-select" required>
                                <option value="">Seleccione...</option>
                                @foreach($components as $component)
                                    <option value="{{ $component->id }}"
                                        {{ (int)($item['component_product_id'] ?? $item['component_product_id'] ?? 0) === $component->id ? 'selected' : '' }}>
                                        {{ $component->name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input type="number" step="0.0001" min="0.0001" name="items[{{ $index }}][quantity]" class="form-control"
                                   value="{{ $item['quantity'] ?? '' }}" required>
                        </td>
                        <td>
                            <input type="text" name="items[{{ $index }}][unit]" class="form-control"
                                   value="{{ $item['unit'] ?? '' }}" placeholder="kg, g, l, ml, unidad...">
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" max="100" name="items[{{ $index }}][wastage_percent]" class="form-control"
                                   value="{{ $item['wastage_percent'] ?? 0 }}">
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-danger" onclick="removeRecipeRow(this)">X</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-muted">Aún no hay insumos definidos. Usa el botón "Añadir insumo" para comenzar.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <button type="button" class="btn btn-outline-secondary mb-3" onclick="addRecipeRow()">Añadir insumo</button>

        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Guardar receta</button>
            <a href="{{ route('products.edit', $product) }}" class="btn btn-secondary">Volver al producto</a>
        </div>
    </form>

    <template id="recipe-row-template">
        <tr>
            <td>
                <select class="form-select" data-name="component_product_id">
                    <option value="">Seleccione...</option>
                    @foreach($components as $component)
                        <option value="{{ $component->id }}">{{ $component->name }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <input type="number" step="0.0001" min="0.0001" class="form-control" data-name="quantity">
            </td>
            <td>
                <input type="text" class="form-control" data-name="unit" placeholder="kg, g, l, ml, unidad...">
            </td>
            <td>
                <input type="number" step="0.01" min="0" max="100" class="form-control" data-name="wastage_percent" value="0">
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger" onclick="removeRecipeRow(this)">X</button>
            </td>
        </tr>
    </template>

    <script>
        let recipeRowIndex = {{ is_array($oldItems) ? count($oldItems) : 0 }};

        function addRecipeRow() {
            const tbody = document.getElementById('recipe-items-body');
            const template = document.getElementById('recipe-row-template').content.cloneNode(true);

            template.querySelectorAll('[data-name]').forEach(function (el) {
                const field = el.getAttribute('data-name');
                el.setAttribute('name', `items[${recipeRowIndex}][${field}]`);
            });

            // Si la tabla estaba vacía
            const emptyRow = tbody.querySelector('tr td[colspan]');
            if (emptyRow) {
                emptyRow.parentElement.remove();
            }

            tbody.appendChild(template);
            recipeRowIndex++;
        }

        function removeRecipeRow(button) {
            const row = button.closest('tr');
            row.remove();
        }
    </script>
@endsection
