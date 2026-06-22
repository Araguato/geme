@extends('layout')

@section('content')
    <h1>Ajuste de inventario</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('stock.adjust') }}" method="POST" class="mt-3">
        @csrf

        <div class="mb-3">
            <label class="form-label">Producto</label>
            <select name="product_id" class="form-select" required onchange="this.form.submit()">
                <option value="">Seleccione...</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" {{ optional($selectedProduct)->id === $product->id ? 'selected' : '' }}>
                        {{ $product->name }}
                    </option>
                @endforeach
            </select>
        </div>

        @if($selectedProduct)
            <div class="alert alert-info py-2 mb-3">
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

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label">Tipo de movimiento</label>
                <select name="type" class="form-select" required>
                    <option value="in" {{ old('type') === 'in' ? 'selected' : '' }}>Entrada (compra, carga inicial)</option>
                    <option value="out" {{ old('type') === 'out' ? 'selected' : '' }}>Salida (consumo, merma)</option>
                    <option value="adjustment" {{ old('type') === 'adjustment' ? 'selected' : '' }}>Ajuste manual</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Motivo</label>
                <input type="text"
                       name="reason"
                       class="form-control"
                       value="{{ old('reason', 'Compra / entrada') }}"
                       maxlength="50"
                       required>
            </div>
        </div>

        <div class="row g-3 mb-3">
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
@endsection