<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', \App\Models\Setting::get('business_name', config('app.name', 'Tu negocio')))</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('logo-quorisk.jpg') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .hero { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: white; padding: 4rem 0; }
        .product-card { transition: transform .15s ease, box-shadow .15s ease; }
        .product-card:hover { transform: translateY(-4px); box-shadow: 0 10px 25px rgba(0,0,0,.1); }
    </style>
    @stack('styles')
</head>
<body class="d-flex flex-column min-vh-100">
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
                        <li class="nav-item">
                            <a class="nav-link position-relative" href="{{ route('public.cart') }}">
                                <i class="bi bi-cart3"></i>
                                @php $cartCount = count(session('cart', [])); @endphp
                                @if($cartCount > 0)
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        {{ $cartCount }}
                                    </span>
                                @endif
                            </a>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="{{ url('/dashboard') }}">Mi cuenta</a></li>
                    @else
                        <li class="nav-item"><a class="nav-link" href="{{ route('customer.register') }}">Registrarme</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Iniciar sesión</a></li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-0 mb-0" role="alert">
            <div class="container">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-0 mb-0" role="alert">
            <div class="container">{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <main class="flex-grow-1">
        @yield('content')
    </main>

    <footer class="bg-light border-top py-4 mt-auto">
        <div class="container text-center text-muted">
            <p class="mb-1">&copy; {{ date('Y') }} {{ $companyName }}</p>
            <p class="small mb-0">RIF {{ \App\Models\Setting::get('company_tax_id', '') }}</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
