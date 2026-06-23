@extends('layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Etiqueta de producto</h1>
    <button onclick="window.print()" class="btn btn-primary">Imprimir</button>
</div>

<div class="card p-4 text-center" id="label-card" style="max-width: 400px; margin: 0 auto;">
    <h4 class="mb-1">{{ $product->name }}</h4>
    <h2 class="text-success mb-3">$ {{ number_format($product->price, 2) }}</h2>
    @if($product->description)
        <p class="mb-1 small">{{ Str::limit($product->description, 100) }}</p>
    @endif
    @if($product->description_zh)
        <p class="mb-3 small text-muted">{{ Str::limit($product->description_zh, 100) }}</p>
    @endif
    @if($product->sku)
        <p class="small text-muted mb-2">SKU: {{ $product->sku }}</p>
    @endif
    <div class="row">
        <div class="col-6">
            <div id="qrcode-staff" class="d-flex justify-content-center my-2"></div>
            <p class="small text-muted mb-0">Empleados</p>
        </div>
        <div class="col-6">
            <div id="qrcode-customer" class="d-flex justify-content-center my-2"></div>
            <p class="small text-muted mb-0">Clientes</p>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    (function() {
        const staffUrl = '{{ route('products.label', $product) }}';
        new QRCode(document.getElementById('qrcode-staff'), {
            text: staffUrl,
            width: 140,
            height: 140,
            colorDark: '#000000',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.M
        });

        const customerUrl = '{{ route('catalog.show', $product) }}';
        new QRCode(document.getElementById('qrcode-customer'), {
            text: customerUrl,
            width: 140,
            height: 140,
            colorDark: '#000000',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.M
        });
    })();
</script>

<style media="print">
    body * {
        visibility: hidden;
    }
    #label-card, #label-card * {
        visibility: visible;
    }
    #label-card {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        max-width: none;
        border: none;
    }
    .btn {
        display: none !important;
    }
</style>
@endsection
