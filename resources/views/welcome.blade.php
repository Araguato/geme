<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ \App\Models\Setting::get('business_name', config('app.name', 'Inicio')) }}</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('logo-quorisk.jpg') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .hero { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: white; padding: 5rem 0; }
        .product-card { transition: transform .15s ease, box-shadow .15s ease; }
        .product-card:hover { transform: translateY(-4px); box-shadow: 0 10px 25px rgba(0,0,0,.1); }
    </style>
</head>
<body>
    @php
        $businessName = \App\Models\Setting::get('business_name', config('app.name', 'Tu negocio'));
        $companyName = \App\Models\Setting::get('company_name', $businessName);
    @endphp

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="{{ url('/') }}">
                <img src="{{ asset('logo-quorisk.jpg') }}" alt="{{ $businessName }}" style="height: 32px; width: 32px; object-fit: cover; border-radius: 50%;">
                {{ $businessName }}
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="{{ route('catalog.index') }}">Catálogo</a></li>
                    @auth
                        <li class="nav-item"><a class="nav-link" href="{{ route('public.order.index') }}">Hacer pedido</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ url('/dashboard') }}">Mi cuenta</a></li>
                    @else
                        <li class="nav-item"><a class="nav-link" href="{{ route('customer.register') }}">Registrarme</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Iniciar sesión</a></li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="container text-center">
            <h1 class="display-4 fw-bold mb-3">{{ $companyName }}</h1>
            <p class="lead mb-4">Gestión de inventario, ventas y compras alineadas con SENIAT.</p>
            <a href="{{ route('catalog.index') }}" class="btn btn-success btn-lg me-2">Ver catálogo</a>
            @auth
                <a href="{{ route('public.order.index') }}" class="btn btn-primary btn-lg me-2">Hacer pedido</a>
                <a href="{{ url('/dashboard') }}" class="btn btn-outline-light btn-lg">Mi cuenta</a>
            @else
                <a href="{{ route('customer.register') }}" class="btn btn-primary btn-lg me-2">Registrarme</a>
                <a href="{{ route('login') }}" class="btn btn-outline-light btn-lg">Acceder al sistema</a>
            @endauth
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <h2 class="mb-4">Productos destacados</h2>
            @php
                $featured = \App\Models\Product::where('is_active', true)
                    ->where('is_raw_material', false)
                    ->orderByDesc('created_at')
                    ->limit(6)
                    ->get();
            @endphp
            <div class="row g-4">
                @forelse($featured as $product)
                    <div class="col-md-4 col-sm-6">
                        <div class="card h-100 product-card">
                            <div class="card-body">
                                <h5 class="card-title">{{ $product->name }}</h5>
                                <p class="card-text text-muted">{{ Str::limit($product->description, 80) }}</p>
                                <p class="fw-bold text-success">$ {{ number_format($product->price, 2) }}</p>
                                <a href="{{ route('catalog.show', $product) }}" class="btn btn-outline-primary btn-sm">Ver detalle</a>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted">Próximamente publicaremos nuestros productos.</p>
                @endforelse
            </div>
            @if($featured->count() > 0)
                <div class="text-center mt-4">
                    <a href="{{ route('catalog.index') }}" class="btn btn-dark">Ver todos los productos</a>
                </div>
            @endif
        </div>
    </section>

    <footer class="bg-light border-top py-4 mt-auto">
        <div class="container text-center text-muted">
            <p class="mb-1">&copy; {{ date('Y') }} {{ $companyName }}</p>
            <p class="small mb-0">RIF {{ \App\Models\Setting::get('company_tax_id', '') }}</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
