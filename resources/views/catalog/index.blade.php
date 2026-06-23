<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo - GEME</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <h1 class="mb-4">Catálogo de productos</h1>

    <form method="GET" action="{{ route('catalog.index') }}" class="row g-2 mb-4">
        <div class="col-md-5">
            <select name="category_id" class="form-select" onchange="this.form.submit()">
                <option value="">Todas las categorías</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ $categoryId == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-5">
            <input type="text" name="search" class="form-control" placeholder="Buscar producto..." value="{{ $search ?? '' }}">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-outline-secondary w-100">Buscar</button>
        </div>
    </form>

    <div class="row g-3">
        @foreach($products as $product)
            <div class="col-md-4 col-lg-3">
                <div class="card h-100">
                    @if($product->image_path)
                        <img src="{{ asset('storage/' . $product->image_path) }}" class="card-img-top" alt="{{ $product->name }}" style="height: 160px; object-fit: cover;">
                    @endif
                    <div class="card-body">
                        <h5 class="card-title">{{ $product->name }}</h5>
                        <p class="card-text text-success fw-bold">$ {{ number_format($product->price, 2) }}</p>
                        <p class="card-text small">{{ Str::limit($product->description, 80) }}</p>
                        @if($product->description_zh)
                            <p class="card-text small text-muted">{{ Str::limit($product->description_zh, 80) }}</p>
                        @endif
                        <a href="{{ route('catalog.show', $product) }}" class="btn btn-sm btn-primary">Ver más</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
</body>
</html>
