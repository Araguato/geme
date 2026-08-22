<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $product->name }} - Catálogo GEME</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                @if($product->mainImage)
                    <img src="{{ asset('storage/' . $product->mainImage->path) }}" class="card-img-top" alt="{{ $product->name }}" style="max-height: 300px; object-fit: cover;">
                @endif
                <div class="card-body">
                    <h2 class="card-title">{{ $product->name }}</h2>
                    <h3 class="text-success">$ {{ number_format($product->price, 2) }}</h3>
                    @if($product->description)
                        <p class="card-text">{{ $product->description }}</p>
                    @endif
                    @if($product->description_zh)
                        <div class="border-start border-3 ps-3 mt-3">
                            <p class="text-body-secondary mb-1">Descripción en chino:</p>
                            <p class="card-text">{{ $product->description_zh }}</p>
                        </div>
                    @endif
                    @if($product->sku)
                        <p class="text-body-secondary mt-2">SKU: {{ $product->sku }}</p>
                    @endif

                    @if($product->isParent() && $product->variants->count() > 0)
                        <div class="mt-3">
                            <h5>Variantes disponibles</h5>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Variante</th>
                                            <th>SKU</th>
                                            <th>Precio</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($product->variants as $variant)
                                            <tr>
                                                <td>
                                                    @foreach($variant->variant_attributes ?? [] as $attr => $val)
                                                        <span class="badge bg-secondary me-1">{{ $attr }}: {{ $val }}</span>
                                                    @endforeach
                                                </td>
                                                <td>{{ $variant->sku ?? '-' }}</td>
                                                <td>$ {{ number_format($variant->price, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if($product->images->count() > 1)
                        <div class="row g-2 mt-3">
                            @foreach($product->images as $image)
                                <div class="col-4 col-md-3">
                                    <img src="{{ asset('storage/' . $image->path) }}" class="img-thumbnail w-100" style="height: 100px; object-fit: cover;" alt="">
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="d-flex justify-content-center my-3">
                        <div id="qrcode"></div>
                    </div>
                    <p class="text-center text-body-secondary small">Escanea este QR para compartir el producto</p>
                    <a href="{{ route('catalog.index') }}" class="btn btn-outline-secondary">Volver al catálogo</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    new QRCode(document.getElementById('qrcode'), {
        text: '{{ route('catalog.show', $product) }}',
        width: 180,
        height: 180,
    });
</script>
</body>
</html>
