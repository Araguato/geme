@extends('layout')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Buscar productos</h1>
    </div>

    <form method="GET" action="{{ route('products.search') }}" class="mb-4">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Nombre, SKU o código de barras..." value="{{ $search ?? '' }}" autofocus>
            <button type="submit" class="btn btn-primary">Buscar</button>
        </div>
    </form>

    @if($search && $products->isEmpty())
        <div class="alert alert-info">No se encontraron productos.</div>
    @endif

    <div class="row g-3">
        @foreach($products as $product)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    @if($product->mainImage)
                        <img src="{{ asset('storage/' . $product->mainImage->path) }}" class="card-img-top" alt="{{ $product->name }}" style="height: 180px; object-fit: cover;">
                    @else
                        <div class="card-img-top bg-secondary-subtle d-flex align-items-center justify-content-center" style="height: 180px;">
                            <i class="bi bi-image text-secondary fs-1"></i>
                        </div>
                    @endif
                    <div class="card-body">
                        <h5 class="card-title">{{ $product->name }}</h5>
                        <p class="card-text text-muted mb-1">{{ $product->category?->name ?? 'Sin categoría' }}</p>
                        <p class="card-text text-success fw-bold">$ {{ number_format($product->price, 2) }}</p>

                        @if($product->barcodes->isNotEmpty())
                            <p class="card-text small mb-1">
                                <strong>Barcodes:</strong>
                                {{ $product->barcodes->pluck('barcode')->implode(', ') }}
                            </p>
                        @endif

                        @if($product->sku)
                            <p class="card-text small mb-1"><strong>SKU:</strong> {{ $product->sku }}</p>
                        @endif

                        @if($product->description)
                            <p class="card-text small">{{ Str::limit($product->description, 120) }}</p>
                        @endif

                        @if($product->images->count() > 1)
                            <div class="d-flex gap-2 mt-2 flex-wrap">
                                @foreach($product->images as $image)
                                    <img src="{{ asset('storage/' . $image->path) }}" class="rounded" style="width: 60px; height: 60px; object-fit: cover;" alt="">
                                @endforeach
                            </div>
                        @endif

                        <div class="mt-3 text-center">
                            <p class="small text-muted mb-1">Escanea para ver la ficha</p>
                            <img src="https://chart.googleapis.com/chart?cht=qr&chs=150x150&chl={{ urlencode(route('catalog.show', $product)) }}" alt="QR" class="img-fluid">
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
