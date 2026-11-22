<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f172a">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <title>geme - Admin</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('logo-quorisk.jpg') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intro.js@7.2.0/minified/introjs.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    @php
        $storedPrimary = \App\Models\Setting::get('theme_primary_color', '#0f172a');
        $storedAccent = \App\Models\Setting::get('theme_accent_color', '#22c55e');
        $themeVariant = \App\Models\Setting::get('theme_variant', 'classic');

        // Definir colores según esquema
        switch ($themeVariant) {
            case 'light':
                // Esquema claro fijo
                $themePrimaryColor = '#1f2937';
                $themeAccentColor = '#38bdf8';
                break;
            case 'dark':
                // Esquema oscuro fijo
                $themePrimaryColor = '#0b1220';
                $themeAccentColor = '#22c55e';
                break;
            case 'classic':
            case 'custom':
            default:
                // Usar siempre los colores configurados en Apariencia (con fallback por defecto)
                $themePrimaryColor = $storedPrimary ?: '#0f172a';
                $themeAccentColor = $storedAccent ?: '#22c55e';
                break;
        }

        $isLight = ($themeVariant === 'light');

        $themeFontScale = (float) \App\Models\Setting::get('theme_font_scale', '1.05');
        $themeBackgroundMode = \App\Models\Setting::get('theme_background_mode', 'gradient');
        $themeBackgroundImageUrl = \App\Models\Setting::get('theme_background_image_url', '');
        $themeLogoUrl = \App\Models\Setting::get('theme_logo_url', '');
        $businessName = \App\Models\Setting::get('business_name', 'geme');
    @endphp
    <style>
        body {
            font-size: {{ $themeFontScale }}rem;
            @if($isLight)
                /* Tema claro: fondo muy claro, texto oscuro */
                background:
                    radial-gradient(circle at top left, rgba(15, 23, 42, 0.05), transparent 55%),
                    radial-gradient(circle at bottom right, rgba(56, 189, 248, 0.10), transparent 55%),
                    linear-gradient(135deg, #f8fafc 0%, #e5e7eb 40%, #dbeafe 80%, rgba(56, 189, 248, 0.30) 100%);
                color: #0f172a;
            @else
                @if($themeBackgroundMode === 'image' && $themeBackgroundImageUrl)
                    background:
                        radial-gradient(circle at top left, rgba(255, 255, 255, 0.10), transparent 55%),
                        radial-gradient(circle at bottom right, rgba(34, 197, 94, 0.10), transparent 55%),
                        linear-gradient(135deg, #020617 0%, #0b1220 45%, {{ $themePrimaryColor }} 80%, rgba(34, 197, 94, 0.20) 100%),
                        url('{{ $themeBackgroundImageUrl }}') center center / cover fixed no-repeat;
                @else
                    background:
                        radial-gradient(circle at top left, rgba(255, 255, 255, 0.10), transparent 55%),
                        radial-gradient(circle at bottom right, rgba(34, 197, 94, 0.10), transparent 55%),
                        linear-gradient(135deg, #020617 0%, #0b1220 45%, {{ $themePrimaryColor }} 80%, rgba(34, 197, 94, 0.20) 100%);
                @endif
                color: #eef2ff;
            @endif
            background-attachment: fixed;
        }

        .navbar-geme {
            @if($isLight)
                background: linear-gradient(90deg, rgba(248, 250, 252, 0.98) 0%, rgba(241, 245, 249, 0.96) 40%, rgba(226, 232, 240, 0.95) 70%, rgba(56, 189, 248, 0.25) 100%);
            @else
                background: linear-gradient(90deg, rgba(2, 6, 23, 0.92) 0%, rgba(15, 23, 42, 0.90) 40%, rgba(15, 23, 42, 0.85) 70%, rgba(34, 197, 94, 0.22) 100%);
            @endif
            box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.6);
            padding-top: 0.9rem;
            padding-bottom: 0.9rem;
            backdrop-filter: blur(8px);
            position: relative;
            z-index: 1030;
        }

        .navbar-geme .navbar-brand,
        .navbar-geme .nav-link,
        .navbar-geme .navbar-toggler-icon {
            color: {{ $isLight ? '#0f172a' : '#e2e8f0' }} !important;
        }

        .navbar-geme .nav-link,
        .navbar-geme .navbar-brand {
            font-size: 1.1rem;
            font-weight: 600;
        }

        /* Botón de menú (☰) en tablet y móvil: visible en ambos temas */
        .navbar-geme .navbar-toggler {
            border: 1px solid {{ $isLight ? 'rgba(15, 23, 42, 0.35)' : 'rgba(226, 232, 240, 0.35)' }};
            padding: 0.35rem 0.6rem;
        }
        .navbar-geme .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='{{ $isLight ? 'rgba%2815, 23, 42, 0.9%29' : 'rgba%28226, 232, 240, 0.95%29' }}' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e") !important;
        }
        .navbar-geme .navbar-brand-home {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            max-width: 60vw;
        }
        .navbar-geme .navbar-brand-home img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }
        .navbar-geme .navbar-brand-home span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Pantallas pequeñas: menú desplegado legible y tablas con desplazamiento horizontal */
        @media (max-width: 991.98px) {
            .navbar-geme .navbar-collapse {
                max-height: calc(100vh - 70px);
                overflow-y: auto;
                padding-bottom: 0.75rem;
            }
        }
        @media (max-width: 767.98px) {
            .container table.table {
                display: block;
                width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
        }
        @media (max-width: 575.98px) {
            .container {
                padding: 1rem 0.75rem;
            }
        }

        .navbar-geme .nav-link.active,
        .navbar-geme .nav-link.show,
        .navbar-geme .nav-link:focus,
        .navbar-geme .nav-link:hover {
            @if($isLight)
                color: {{ $themeAccentColor }} !important;
            @else
                color: #ffffff !important;
                text-shadow: 0 0 6px rgba(0, 0, 0, 0.6);
            @endif
        }

        .dropdown-menu {
            @if($isLight)
                background-color: #ffffff;
                border-color: {{ $themeAccentColor }};
            @else
                background-color: #020617;
                border-color: {{ $themeAccentColor }};
            @endif
            z-index: 1040;
        }

        .dropdown-item {
            color: {{ $isLight ? '#0f172a' : '#f8f1e1' }};
        }

        .dropdown-item:hover,
        .dropdown-item:focus {
            background-color: {{ $themePrimaryColor }};
            color: #ffffff;
        }

        .container {
            border-radius: 0.75rem;
            padding: 1.5rem 1.75rem;
            box-shadow: none;
            @if($isLight)
                background-color: rgba(255, 255, 255, 0.85);
                color: #0f172a;
            @else
                background-color: transparent;
                color: #fdf5e6;
            @endif
        }

        .btn-primary,
        .btn-success,
        .btn-danger,
        .btn-secondary {
            border-radius: 999px;
            font-weight: 600;
            letter-spacing: 0.03em;
        }

        .btn-primary {
            background: linear-gradient(135deg, {{ $themePrimaryColor }}, {{ $themeAccentColor }});
            border-color: {{ $themeAccentColor }};
            color: {{ $isLight ? '#ffffff' : '#2b1a0a' }};
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, {{ $themeAccentColor }}, {{ $themePrimaryColor }});
            border-color: {{ $themeAccentColor }};
            color: #ffffff;
        }

        .table {
            color: {{ $isLight ? '#0f172a' : '#fdf5e6' }};
        }

        .table thead {
            background-color: {{ $isLight ? '#e5e7eb' : 'rgba(0, 0, 0, 0.75)' }};
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: {{ $isLight ? '#f9fafb' : 'rgba(255, 255, 255, 0.03)' }};
        }

        .table-striped tbody tr:nth-of-type(even) {
            background-color: {{ $isLight ? '#e5e7eb' : 'rgba(0, 0, 0, 0.35)' }};
        }

        .form-control,
        .form-select {
            @if($isLight)
                background-color: #ffffff;
                border-color: {{ $themeAccentColor }};
                color: #0f172a;
            @else
                background-color: rgba(0, 0, 0, 0.6);
                border-color: {{ $themeAccentColor }}99;
                color: #fdf5e6;
            @endif
        }

        .form-control:focus,
        .form-select:focus {
            border-color: {{ $themeAccentColor }};
            box-shadow: 0 0 0 0.2rem rgba(148, 163, 184, 0.45);
        }

        .card {
            background-color: {{ $isLight ? '#ffffff' : '#0b1220' }};
            color: {{ $isLight ? '#0f172a' : '#fdf5e6' }};
            border-color: {{ $isLight ? '#e5e7eb' : '#1e293b' }};
        }

        .card-header {
            background-color: {{ $isLight ? '#f8fafc' : 'rgba(0, 0, 0, 0.35)' }};
            color: {{ $isLight ? '#0f172a' : '#fdf5e6' }};
            border-bottom-color: {{ $isLight ? '#e5e7eb' : '#1e293b' }};
        }

        .form-label,
        .form-text {
            color: {{ $isLight ? '#4b5563' : '#e2e8f0' }};
        }

        a {
            color: {{ $themeAccentColor }};
        }

        a:hover {
            color: {{ $isLight ? '#0f172a' : '#ffe28a' }};
        }

        .text-muted {
            color: {{ $isLight ? '#374151' : '#94a3b8' }} !important;
        }

        .card .text-muted {
            color: inherit !important;
            opacity: 0.75;
        }

        /* Paneles e informes: listas y tablas que siguen el tema (claro / oscuro) */
        .geme-panel .list-group-item {
            background-color: transparent;
            color: inherit;
            border-color: {{ $isLight ? '#e5e7eb' : 'rgba(148, 163, 184, 0.18)' }};
        }
        .geme-panel .table {
            --bs-table-bg: transparent;
            --bs-table-color: inherit;
            --bs-table-hover-bg: rgba(148, 163, 184, 0.10);
            --bs-table-hover-color: inherit;
            --bs-table-border-color: {{ $isLight ? '#e5e7eb' : 'rgba(148, 163, 184, 0.18)' }};
        }
        .geme-panel .table-light {
            --bs-table-bg: {{ $isLight ? '#f1f5f9' : 'rgba(0, 0, 0, 0.35)' }};
            --bs-table-color: inherit;
        }
        .geme-panel .nav-tabs { border-bottom-color: {{ $isLight ? '#e5e7eb' : '#1e293b' }}; }
        .geme-panel .nav-tabs .nav-link { color: inherit; opacity: 0.75; }
        .geme-panel .nav-tabs .nav-link.active {
            opacity: 1;
            background-color: {{ $isLight ? '#ffffff' : '#0b1220' }};
            color: inherit;
            border-color: {{ $isLight ? '#e5e7eb #e5e7eb #ffffff' : '#1e293b #1e293b #0b1220' }};
        }
        .geme-panel .progress { background-color: rgba(148, 163, 184, 0.2); }
        .geme-panel .card > .table-responsive > .table > :not(caption) > * > *:first-child { padding-left: 1rem; }
        .geme-panel .card > .table-responsive > .table > :not(caption) > * > *:last-child { padding-right: 1rem; }

        .form-control::placeholder,
        .form-select::placeholder {
            color: {{ $isLight ? '#9ca3af' : '#f0dfc4' }};
            opacity: 0.9;
        }

        .introjs-overlay {
            background: rgba(0, 0, 0, 0.75) !important;
        }

        .introjs-tooltip {
            color: #0f172a !important;
            background-color: #ffffff !important;
            border-radius: 0.75rem !important;
            box-shadow: 0 0.75rem 2rem rgba(0, 0, 0, 0.55) !important;
        }

        .introjs-tooltiptext {
            color: #0f172a !important;
        }

        .introjs-tooltip a {
            color: #0f172a !important;
            text-decoration: underline;
        }

        .introjs-button {
            color: #0f172a !important;
            background: #f1f5f9 !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 0.5rem !important;
            text-shadow: none !important;
        }

        .introjs-button:hover {
            background: #e2e8f0 !important;
        }

        .introjs-skipbutton,
        .introjs-skipbutton:hover {
            color: #334155 !important;
            background: transparent !important;
            border: none !important;
        }

        .introjs-helperLayer {
            box-shadow:
                0 0 0 3px {{ $themeAccentColor }},
                0 0.75rem 2rem rgba(0, 0, 0, 0.55) !important;
            border-radius: 0.75rem !important;
        }

        /* Para usar imagen de fondo establece en Apariencia:
           - Fondo: Imagen
           - URL: una ruta accesible por el navegador, por ejemplo
             /storage/fondos/restaurante.jpg o https://...,
           no una ruta local tipo C:\\... */
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark navbar-geme mb-4">
    <div class="container-fluid">
        {{-- En tablet y móvil: acceso directo a Inicio junto al botón de menú --}}
        <a class="navbar-brand navbar-brand-home d-lg-none" href="{{ auth()->check() && auth()->user()->isStaff() ? route('home') : url('/') }}" title="Inicio">
            <img src="{{ $themeLogoUrl ?: asset('logo-quorisk.jpg') }}" alt="">
            <span>{{ $businessName }}</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
                aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item d-none" id="tourNavItem">
                    <a class="nav-link" href="javascript:void(0)" onclick="startGemeTour()" title="Iniciar tour de ayuda">
                        <i class="bi bi-question-circle"></i> Tour
                    </a>
                </li>
                @auth
                    @php($user = auth()->user())
                    @php($isAdmin = $user->hasRole('admin'))
                    @php($isSupervisor = $user->hasRole('supervisor'))
                    @php($isCajero = $user->hasRole('cajero'))
                    @php($isVendedor = $user->hasRole('vendedor'))
                    @php($isAlmacenista = $user->hasRole('almacenista'))
                    @php($isDespachador = $user->hasRole('despachador'))
                    @php($isContable = $user->hasRole('contable'))
                    @php($isStaff = $user->isStaff())
                    @php($canFinance = $isAdmin || $isSupervisor || $isContable)
                    @php($canExpenses = $isAdmin || $isContable)
                    @php($financesEnabled = (bool) \App\Models\Setting::get('finances_enabled', 0))

                    @if($isStaff)
                        {{-- Inicio: pantalla de trabajo según el rol --}}
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('home') }}"><i class="bi bi-house"></i> Inicio</a>
                        </li>

                        {{-- Menú Finanzas: admin, supervisor y contable --}}
                        @if($canFinance)
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="financesDropdown" role="button"
                                   data-bs-toggle="dropdown" aria-expanded="false">
                                    {{ __('ui.nav.finances_menu') }}
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="financesDropdown">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('accounting.dashboard') }}"><i class="bi bi-calculator"></i> Panel financiero</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('reports.sales') }}"><i class="bi bi-bar-chart"></i> Ventas por vendedor / categoría</a>
                                    </li>
                                    @if($financesEnabled && $canExpenses)
                                        <li>
                                            <a class="dropdown-item" href="{{ route('finances.index') }}">{{ __('ui.nav.finances') }}</a>
                                        </li>
                                    @endif
                                    <li><hr class="dropdown-divider"></li>
                                    <li class="px-3 text-muted small">Finanzas</li>
                                    @if($canExpenses)
                                        <li>
                                            <a class="dropdown-item" href="{{ route('settings.bcv.edit') }}">{{ __('ui.nav.bcv_rate') }}</a>
                                        </li>
                                    @endif
                                    <li>
                                        <a class="dropdown-item" href="{{ route('supplier-invoices.index') }}">{{ __('ui.layout.accounts_payable') }}</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('supplier-ap.dashboard') }}">{{ __('ui.layout.ap_dashboard') }}</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('recurring-supplier-invoices.index') }}">Facturas recurrentes (CxP)</a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li class="px-3 text-muted small">Reportes finanzas</li>
                                    @if($financesEnabled && $canExpenses)
                                        <li>
                                            <a class="dropdown-item" href="{{ route('finances.reports.monthly') }}">{{ __('ui.nav.finances_report_monthly') }}</a>
                                        </li>
                                    @endif
                                    <li>
                                        <a class="dropdown-item" href="{{ route('fiscal-ledger.index') }}">Libro Electrónico SENIAT</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('fiscal-ledger.tax-report') }}">Resumen de impuestos</a>
                                    </li>
                                </ul>
                            </li>
                        @endif

                        {{-- Marketing: admin y supervisor --}}
                        @if($isAdmin || $isSupervisor)
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('marketing.index') }}"><i class="bi bi-megaphone"></i> Marketing</a>
                            </li>
                        @endif

                        {{-- Menú Ventas: admin, cajero, vendedor, supervisor --}}
                        @if($isAdmin || $isCajero || $isVendedor || $isSupervisor)
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="salesDropdown" role="button"
                                   data-bs-toggle="dropdown" aria-expanded="false">
                                    Ventas
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="salesDropdown">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('pos.index') }}">TPV / Punto de venta</a>
                                    </li>
                                </ul>
                            </li>
                        @endif

                        {{-- Menú Inventario: admin, almacenista, vendedor (consulta), supervisor --}}
                        @if($isAdmin || $isAlmacenista || $isVendedor || $isSupervisor || $isDespachador)
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="inventoryDropdown" role="button"
                                   data-bs-toggle="dropdown" aria-expanded="false">
                                    Inventario
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="inventoryDropdown">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('products.search') }}">Buscar productos</a>
                                    </li>
                                    @if($isAdmin || $isAlmacenista || $isSupervisor)
                                        <li>
                                            <a class="dropdown-item" href="{{ route('inventory.dashboard') }}"><i class="bi bi-speedometer2"></i> Panel de almacén</a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li class="px-3 text-muted small">Catálogos</li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('categories.index') }}">{{ __('ui.nav.categories') }}</a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('products.index') }}">{{ __('ui.nav.products') }}</a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('products.prices.edit') }}">Actualizar precios</a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li class="px-3 text-muted small">Depósitos</li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('warehouses.index') }}">Sitios / Depósitos</a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('locations.index') }}">Ubicaciones</a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li class="px-3 text-muted small">Stock</li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('stock.index') }}">{{ __('ui.layout.current_stock') }}</a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('stock.adjust.form') }}">{{ __('ui.layout.inventory_adjustments') }}</a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('unit-conversions.index') }}">Conversiones de unidades</a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif

                        {{-- Menú Nómina: admin y supervisor --}}
                        @if($isAdmin || $isSupervisor)
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="payrollDropdown" role="button"
                                   data-bs-toggle="dropdown" aria-expanded="false">
                                    Nómina
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="payrollDropdown">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('payroll-periods.index') }}">{{ __('ui.nav.payroll_periods') }}</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('payroll-runs.index') }}">{{ __('ui.nav.payroll_runs') }}</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('payroll-entries.index') }}">{{ __('ui.nav.payroll_entries') }}</a>
                                    </li>
                                </ul>
                            </li>
                        @endif

                        {{-- Menú Administración: solo admin --}}
                        @if($isAdmin)
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button"
                                   data-bs-toggle="dropdown" aria-expanded="false">
                                    {{ __('ui.nav.admin') }}
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('dashboard') }}">{{ __('ui.nav.dashboard') }}</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('help.index') }}">{{ __('ui.nav.help_center') }}</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('users.index') }}">{{ __('ui.nav.users') }}</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('login-logs.index') }}">Registro de logins</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('sales-locations.index') }}">Ubicaciones de venta</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('settings.localization.edit') }}">{{ __('ui.nav.localization') }}</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('settings.appearance.edit') }}">{{ __('ui.nav.appearance') }}</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('settings.finances.edit') }}">{{ __('ui.nav.finances_settings') }}</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('settings.company.edit') }}">Datos fiscales (SENIAT)</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('settings.ai.edit') }}"><i class="bi bi-stars"></i> Inteligencia artificial</a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li class="px-3 text-muted small">{{ __('ui.layout.people') }}</li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('suppliers.index') }}">{{ __('ui.layout.suppliers') }}</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('employees.index') }}">{{ __('ui.layout.employees') }}</a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li class="px-3 text-muted small">{{ __('ui.layout.admin_section') }}</li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('backup.database') }}">{{ __('ui.layout.backup_db') }}</a>
                                    </li>
                                </ul>
                            </li>
                        @endif
                    @endif
                @endauth
            </ul>

            @auth
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            {{ auth()->user()->name ?? __('ui.nav.user_placeholder') }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <a class="dropdown-item" href="{{ route('help.index') }}">{{ __('ui.nav.help_center') }}</a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('profile.edit') }}">{{ __('ui.nav.my_profile') }}</a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li class="px-3 text-muted small">{{ __('ui.nav.session') }}</li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">{{ __('ui.nav.logout') }}</button>
                                </form>
                            </li>
                            <li>
                                <a href="{{ route('error-report.create') }}" class="dropdown-item text-dark fw-semibold">{{ __('ui.nav.report_problem') }}</a>
                            </li>
                        </ul>
                    </li>
                </ul>
            @endauth
        </div>
    </div>
</nav>

<div class="mb-3 text-center">
    <div class="d-inline-flex align-items-center px-3 py-2 rounded-pill" style="background-color: rgba(0,0,0,0.55); box-shadow: 0 0.35rem 1rem rgba(0,0,0,0.7);">
        <span class="me-3 rounded-circle d-flex justify-content-center align-items-center" style="width: 52px; height: 52px; background: rgba(0,0,0,0.8); overflow: hidden;">
            <img src="{{ $themeLogoUrl ?: asset('logo-quorisk.jpg') }}" alt="Logo de {{ $businessName }}" style="max-width: 100%; max-height: 100%; object-fit: cover;">
        </span>
        <div class="text-start">
            <div class="fw-semibold" style="letter-spacing: 0.04em;">{{ strtoupper($businessName) }}</div>
            <div class="small text-muted">Inventario y Nómina</div>
        </div>
    </div>
</div>

<div class="container">
    @yield('content')
</div>

<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('{{ asset('sw.js') }}');
        });
        // Al cerrar sesión, vaciar también la caché del service worker
        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (form && form.action && form.action.endsWith('/logout') && navigator.serviceWorker.controller) {
                navigator.serviceWorker.controller.postMessage('geme:clear-cache');
            }
        });
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/intro.js@7.2.0/minified/intro.min.js"></script>
<script>
    function startGemeTour() {
        if (window.GEME_TOUR_STEPS && window.GEME_TOUR_STEPS.length > 0 && typeof introJs !== 'undefined') {
            introJs()
                .setOptions({
                    steps: window.GEME_TOUR_STEPS,
                    nextLabel: 'Siguiente',
                    prevLabel: 'Anterior',
                    skipLabel: 'Saltar',
                    doneLabel: 'Listo',
                    showProgress: true,
                    showBullets: true,
                    exitOnOverlayClick: false,
                })
                .start();
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (window.GEME_TOUR_STEPS && window.GEME_TOUR_STEPS.length > 0 && typeof introJs !== 'undefined') {
            const tourNavItem = document.getElementById('tourNavItem');
            if (tourNavItem) {
                tourNavItem.classList.remove('d-none');
            }
        }
    });
</script>
@stack('scripts')
</body>
</html>
