@extends('layouts.public')

@section('title', 'Hacer pedido - ' . \App\Models\Setting::get('business_name', config('app.name')))

@section('content')
<section class="hero">
    <div class="container text-center">
        <h1 class="display-5 fw-bold mb-3">Haz tu pedido</h1>
        <p class="lead mb-0">Selecciona los productos que necesitas. El pago se realiza al retirar o recibir.</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Productos disponibles</h2>
            <a href="{{ route('public.cart') }}" class="btn btn-outline-dark">
                <i class="bi bi-cart3"></i> Ver pedido
                @php $count = count(session('cart', [])); @endphp
                @if($count > 0)<span class="badge bg-danger ms-1">{{ $count }}</span>@endif
            </a>
        </div>

        <div class="row g-4">
            @forelse($products as $product)
                <div class="col-md-4 col-sm-6">
                    <div class="card h-100 product-card">
                        <div class="card-body">
                            <h5 class="card-title">{{ $product->name }}</h5>
                            <p class="card-text text-muted">{{ Str::limit($product->description, 80) }}</p>
                            <p class="fw-bold text-success">$ {{ number_format($product->price, 2) }}</p>

                            <form action="{{ route('public.cart.add') }}" method="POST" class="d-flex gap-2">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                <input type="number" name="quantity" value="1" min="0.001" step="0.001" class="form-control form-control-sm" style="width:90px" required>
                                <button type="submit" class="btn btn-success btn-sm flex-fill">
                                    <i class="bi bi-plus-lg"></i> Agregar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <p class="text-muted">No hay productos disponibles en este momento.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $products->links() }}
        </div>
    </div>
</section>
@endsection
