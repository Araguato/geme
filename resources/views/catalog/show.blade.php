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
                @if($product->image_path)
                    <img src="{{ asset('storage/' . $product->image_path) }}" class="card-img-top" alt="{{ $product->name }}" style="max-height: 300px; object-fit: cover;">
                @endif
                <div class="card-body">
                    <h2 class="card-title">{{ $product->name }}</h2>
                    <h3 class="text-success">$ {{ number_format($product->price, 2) }}</h3>
                    @if($product->description)
                        <p class="card-text">{{ $product->description }}</p>
                    @endif
                    @if($product->description_zh)
                        <div class="border-start border-3 ps-3 mt-3">
                            <p class="text-muted mb-1">Descripción en chino:</p>
                            <p class="card-text">{{ $product->description_zh }}</p>
                        </div>
                    @endif
                    @if($product->sku)
                        <p class="text-muted mt-2">SKU: {{ $product->sku }}</p>
                    @endif
                    <div class="d-flex justify-content-center my-3">
                        <div id="qrcode"></div>
                    </div>
                    <p class="text-center text-muted small">Escanea este QR para compartir el producto</p>
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
