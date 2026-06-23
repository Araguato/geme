@extends('layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h1>Impresión de etiquetas</h1>
    <div>
        <button onclick="window.print()" class="btn btn-primary">
            <i class="bi bi-printer"></i> Imprimir etiquetas
        </button>
    </div>
</div>

<form method="GET" action="{{ route('products.labels.bulk') }}" class="row g-2 align-items-end mb-3 no-print">
    <div class="col-md-3">
        <label class="form-label">Depósito</label>
        <select name="warehouse_id" class="form-select" onchange="this.form.submit()">
            <option value="">Todos</option>
            @foreach($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" {{ (string)($warehouseId ?? '') === (string)$warehouse->id ? 'selected' : '' }}>
                    {{ $warehouse->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Ubicación</label>
        <select name="location_id" class="form-select" onchange="this.form.submit()">
            <option value="">Todas</option>
            @foreach($locations as $location)
                <option value="{{ $location->id }}" {{ (string)($locationId ?? '') === (string)$location->id ? 'selected' : '' }}>
                    {{ $location->name }}@if($location->warehouse) ({{ $location->warehouse->name }})@endif
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Categoría</label>
        <select name="category_id" class="form-select" onchange="this.form.submit()">
            <option value="">Todas</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" {{ (string)($categoryId ?? '') === (string)$category->id ? 'selected' : '' }}>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Buscar</label>
        <div class="input-group">
            <input type="text" name="search" class="form-control" value="{{ $search ?? '' }}" placeholder="Nombre, SKU, barcode...">
            <button type="submit" class="btn btn-outline-secondary">Filtrar</button>
        </div>
    </div>
</form>

<div class="alert alert-info no-print mb-3">
    Selecciona los filtros para imprimir etiquetas de productos. El QR apunta al catálogo público del producto.
</div>

@if($products->isEmpty())
    <div class="alert alert-warning no-print">No hay productos activos para los filtros seleccionados.</div>
@endif

<div class="labels-sheet">
    @foreach($products as $product)
        <div class="product-label">
            <div class="label-header">
                <div class="label-name" title="{{ $product->name }}">{{ Str::limit($product->name, 28) }}</div>
                @if($product->sku)
                    <div class="label-sku">SKU: {{ $product->sku }}</div>
                @endif
            </div>
            <div class="label-body">
                <div class="label-price">
                    $ {{ number_format($product->price, 2) }}
                </div>
                <div class="label-qrcode" id="qrcode-{{ $product->id }}"></div>
            </div>
            <div class="label-footer">
                @if($product->barcodes->first())
                    <div class="label-barcode">{{ $product->barcodes->first()->barcode }}</div>
                @endif
                @if($product->location)
                    <div class="label-location">{{ $product->location->name }}</div>
                @endif
            </div>
        </div>
    @endforeach
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    (function() {
        const products = @json($products->map(function($product) {
            return [
                'id' => $product->id,
                'url' => route('catalog.show', $product)
            ];
        })->values());

        products.forEach(function(product) {
            new QRCode(document.getElementById('qrcode-' + product.id), {
                text: product.url,
                width: 60,
                height: 60,
                colorDark: '#000000',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.M
            });
        });
    })();
</script>

<style>
    .labels-sheet {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: flex-start;
    }

    .product-label {
        width: 48mm;
        height: 32mm;
        border: 1px solid #ccc;
        border-radius: 4px;
        padding: 4px;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        background: #fff;
        font-size: 9px;
        line-height: 1.2;
        page-break-inside: avoid;
    }

    .label-header {
        text-align: center;
    }

    .label-name {
        font-weight: bold;
        font-size: 10px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .label-sku {
        color: #666;
        font-size: 8px;
    }

    .label-body {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 2px 0;
    }

    .label-price {
        font-weight: bold;
        font-size: 14px;
        color: #000;
    }

    .label-qrcode img {
        width: 20mm !important;
        height: 20mm !important;
    }

    .label-footer {
        display: flex;
        justify-content: space-between;
        font-size: 8px;
        color: #555;
    }

    .label-barcode {
        font-family: monospace;
    }

    .label-location {
        text-align: right;
    }

    @media print {
        body * {
            visibility: hidden;
        }
        .labels-sheet, .labels-sheet * {
            visibility: visible;
        }
        .labels-sheet {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            margin: 0;
            padding: 0;
            gap: 0;
        }
        .product-label {
            margin: 0;
            border: none;
            border-bottom: 1px dashed #ddd;
            border-right: 1px dashed #ddd;
        }
        .no-print {
            display: none !important;
        }
    }

    @page {
        size: auto;
        margin: 0;
    }
</style>
@endsection
