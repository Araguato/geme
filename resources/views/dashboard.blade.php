<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl leading-tight">
            {{ __('Panel principal') }}
        </h2>
    </x-slot>

    @php
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole('admin');
        $isSupervisor = $user && $user->hasRole('supervisor');
        $canSeeSummary = $isAdmin || $isSupervisor;
        $storedPrimary = \App\Models\Setting::get('theme_primary_color', '#0f172a');
        $storedAccent = \App\Models\Setting::get('theme_accent_color', '#22c55e');
        $themeVariant = \App\Models\Setting::get('theme_variant', 'classic');
        $isLight = ($themeVariant === 'light');
        $themePrimary = $isLight ? '#1f2937' : ($storedPrimary ?: '#0f172a');
        $themeAccent = $isLight ? '#38bdf8' : ($storedAccent ?: '#22c55e');
        $cardBg = $isLight ? '#ffffff' : '#0b1220';
        $cardText = $isLight ? '#111827' : '#f3f4f6';
        $mutedText = $isLight ? '#4b5563' : '#9ca3af';
        $borderColor = $isLight ? '#e5e7eb' : '#1e293b';
        $pageBg = $isLight ? '#f3f4f6' : '#020617';

        $accentHex = ltrim($themeAccent, '#');
        $accentR = hexdec(substr($accentHex, 0, 2));
        $accentG = hexdec(substr($accentHex, 2, 2));
        $accentB = hexdec(substr($accentHex, 4, 2));
        $accentLuma = (0.299 * $accentR + 0.587 * $accentG + 0.114 * $accentB) / 255;
        $accentTextColor = $accentLuma > 0.5 ? '#0f172a' : '#ffffff';

        $modules = [
            ['id' => 'dashboardPos', 'route' => route('pos.index'), 'label' => 'TPV', 'icon' => 'bi-cart-check'],
            ['id' => 'dashboardProducts', 'route' => route('categories.index'), 'label' => 'Productos', 'icon' => 'bi-box-seam'],
            ['id' => 'dashboardStock', 'route' => route('stock.index'), 'label' => 'Inventario', 'icon' => 'bi-clipboard-data'],
            ['id' => 'dashboardSearch', 'route' => route('products.search'), 'label' => 'Buscar', 'icon' => 'bi-upc-scan'],
            ['id' => 'dashboardWarehouses', 'route' => route('warehouses.index'), 'label' => 'Depósitos', 'icon' => 'bi-building'],
            ['id' => 'dashboardLocations', 'route' => route('locations.index'), 'label' => 'Ubicaciones', 'icon' => 'bi-geo-alt'],
            ['id' => 'dashboardCatalog', 'route' => route('catalog.index'), 'label' => 'Catálogo', 'icon' => 'bi-shop', 'target' => '_blank'],
            ['id' => 'dashboardHelp', 'route' => route('help.index'), 'label' => 'Ayuda', 'icon' => 'bi-question-circle'],
        ];
        if ($isAdmin) {
            $modules = array_merge($modules, [
                ['id' => 'dashboardSuppliers', 'route' => route('suppliers.index'), 'label' => 'Proveedores', 'icon' => 'bi-truck'],
                ['id' => 'dashboardEmployees', 'route' => route('employees.index'), 'label' => 'Empleados', 'icon' => 'bi-people'],
                ['id' => 'dashboardPayroll', 'route' => route('payroll-periods.index'), 'label' => 'Nómina', 'icon' => 'bi-wallet2'],
                ['id' => 'dashboardFinances', 'route' => route('finances.index'), 'label' => 'Finanzas', 'icon' => 'bi-graph-up'],
                ['id' => 'dashboardSettings', 'route' => route('settings.appearance.edit'), 'label' => 'Apariencia', 'icon' => 'bi-palette'],
            ]);
        }
    @endphp

    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    @endpush

    <div class="py-3 py-sm-4" style="background-color: {{ $pageBg }}; color: {{ $cardText }};">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-3">
                <div>
                    <h1 class="text-2xl font-bold" style="color: {{ $cardText }};">Hola, {{ $user->name }}</h1>
                    <p class="text-sm mt-1" style="color: {{ $mutedText }};">Selecciona una acción para empezar.</p>
                </div>
                <button class="btn btn-sm btn-outline-accent hidden sm:inline-block" onclick="startDashboardTour()" style="border-color: {{ $themeAccent }}; color: {{ $themeAccent }};">
                    <i class="bi bi-question-circle"></i> Tour
                </button>
            </div>

            {{-- Estado de caja --}}
            <div class="card mb-2 shadow-sm border-0" style="background-color: {{ $cardBg }}; border: 1px solid {{ $borderColor }};">
                <div class="card-body py-2 px-3 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        @if($activeShift)
                            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Caja abierta</span>
                            <span class="fw-semibold small" style="color: {{ $cardText }};">{{ $activeShift->name }}</span>
                        @else
                            <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle"></i> Caja cerrada</span>
                            <span class="small" style="color: {{ $cardText }};">Abre un turno antes de vender.</span>
                        @endif
                    </div>
                    <a href="{{ route('pos.index') }}" class="btn btn-accent w-100 w-sm-auto" style="background: {{ $themeAccent }}; border-color: {{ $themeAccent }}; color: {{ $accentTextColor }};">
                        <i class="bi bi-cart-check"></i> {{ $activeShift ? 'Ir al TPV' : 'Abrir caja' }}
                    </a>
                </div>
            </div>

            {{-- Módulos principales --}}
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h3 class="h6 mb-0 fw-semibold" style="color: {{ $cardText }};">Módulos</h3>
                    <button class="btn btn-sm btn-outline-accent d-sm-none" onclick="startDashboardTour()" style="border-color: {{ $themeAccent }}; color: {{ $themeAccent }};">
                        <i class="bi bi-question-circle"></i>
                    </button>
                </div>
                <div class="row g-2">
                    @foreach($modules as $mod)
                        <div class="col-3 col-md-3 col-lg-2">
                            <a href="{{ $mod['route'] }}" @if(!empty($mod['target'])) target="{{ $mod['target'] }}" @endif class="dashboard-tile card h-100 text-decoration-none border-0 shadow-sm" id="{{ $mod['id'] }}" style="background: {{ $cardBg }}; border: 1px solid {{ $borderColor }};">
                                <div class="card-body text-center py-2 px-1 dashboard-tile-body">
                                    <div class="mb-1 d-flex justify-content-center align-items-center rounded-circle mx-auto dashboard-icon-bg" style="width: 36px; height: 36px; background: {{ $themeAccent }}22;">
                                        <i class="bi {{ $mod['icon'] }} fs-5" style="color: {{ $themeAccent }};"></i>
                                    </div>
                                    <h6 class="card-title mb-0 dashboard-tile-label" style="color: {{ $cardText }};">{{ $mod['label'] }}</h6>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Resumen del negocio / Ventas del mes: solo admin y supervisor --}}
            @if($canSeeSummary)
                <div class="mb-4">
                    <h3 class="h5 mb-3 fw-semibold" style="color: {{ $cardText }};">Resumen del negocio</h3>
                    <div class="row g-3 mb-4" id="dashboardKpis">
                        <div class="col-6 col-lg-3">
                            <div class="card h-100 border-0 shadow-sm" style="background: {{ $cardBg }}; border: 1px solid {{ $borderColor }};">
                                <div class="card-body">
                                    <h6 class="card-title" style="color: {{ $mutedText }};">Ventas hoy</h6>
                                    <p class="card-text fs-4 fw-bold" style="color: {{ $cardText }};">$ {{ number_format($salesToday, 2) }}</p>
                                    <small style="color: {{ $mutedText }};">{{ $ordersToday }} órdenes</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="card h-100 border-0 shadow-sm" style="background: {{ $cardBg }}; border: 1px solid {{ $borderColor }};">
                                <div class="card-body">
                                    <h6 class="card-title" style="color: {{ $mutedText }};">Ventas del mes</h6>
                                    <p class="card-text fs-4 fw-bold" style="color: {{ $cardText }};">$ {{ number_format($salesMonth, 2) }}</p>
                                    <small style="color: {{ $mutedText }};">{{ $ordersMonth }} órdenes</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="card h-100 border-0 shadow-sm" style="background: {{ $cardBg }}; border: 1px solid {{ $borderColor }};">
                                <div class="card-body">
                                    <h6 class="card-title" style="color: {{ $mutedText }};">Productos activos</h6>
                                    <p class="card-text fs-4 fw-bold" style="color: {{ $cardText }};">{{ $productsCount }}</p>
                                    <small style="color: {{ $mutedText }};">{{ $lowStock }} con stock bajo</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="card h-100 border-0 shadow-sm" style="background: {{ $cardBg }}; border: 1px solid {{ $borderColor }};">
                                <div class="card-body">
                                    <h6 class="card-title" style="color: {{ $mutedText }};">Facturas por pagar</h6>
                                    <p class="card-text fs-4 fw-bold" style="color: {{ $cardText }};">{{ $pendingInvoices }}</p>
                                    <small style="color: {{ $mutedText }};">$ {{ number_format($pendingInvoiceAmount, 2) }}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-12 col-lg-8">
                            <div class="card h-100 border-0 shadow-sm" id="dashboardChart" style="background: {{ $cardBg }}; border: 1px solid {{ $borderColor }};">
                                <div class="card-header fw-bold border-0" style="background: {{ $cardBg }}; color: {{ $cardText }};">Ventas del mes</div>
                                <div class="card-body" style="height: 300px;">
                                    <canvas id="salesChart" height="120"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-4">
                            <div class="card h-100 border-0 shadow-sm" id="dashboardLowStock" style="background: {{ $cardBg }}; border: 1px solid {{ $borderColor }};">
                                <div class="card-header fw-bold border-0" style="background: {{ $cardBg }}; color: {{ $cardText }};">Stock bajo</div>
                                <ul class="list-group list-group-flush">
                                    @forelse($lowStockProducts as $product)
                                        <li class="list-group-item d-flex justify-content-between" style="background: {{ $cardBg }}; color: {{ $cardText }}; border-color: {{ $borderColor }};">
                                            <span>{{ Str::limit($product->name, 25) }}</span>
                                            <span class="badge bg-danger">{{ $product->stock_quantity }}</span>
                                        </li>
                                    @empty
                                        <li class="list-group-item" style="background: {{ $cardBg }}; color: {{ $mutedText }}; border-color: {{ $borderColor }};">No hay productos con stock bajo.</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <style>
        .dashboard-tile {
            transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
        }
        .dashboard-tile:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
            border-color: {{ $themeAccent }} !important;
        }
        .dashboard-tile:hover .dashboard-icon-bg {
            background: {{ $themeAccent }}33 !important;
        }
        .dashboard-tile-label {
            font-size: 0.75rem;
        }
        @media (min-width: 576px) {
            .dashboard-tile-label {
                font-size: 0.85rem;
            }
            .dashboard-tile-body {
                padding-top: 0.75rem !important;
                padding-bottom: 0.75rem !important;
            }
        }
        .btn-accent {
            font-weight: 600;
            border-radius: 999px;
            transition: filter 0.15s ease, transform 0.15s ease;
        }
        .btn-accent:hover {
            filter: brightness(1.1);
            transform: translateY(-1px);
        }
        .btn-outline-accent:hover {
            background: {{ $themeAccent }}22;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script>
        function startDashboardTour() {
            if (typeof introJs === 'undefined') return;
            introJs()
                .setOptions({
                    steps: [
                        { element: '#dashboardPos', intro: 'Desde aquí accedes al TPV para registrar ventas rápidas.' },
                        { element: '#dashboardProducts', intro: 'Crea y edita productos, categorías y precios.' },
                        { element: '#dashboardStock', intro: 'Gestiona stock, entradas, salidas y movimientos.' },
                        { element: '#dashboardSearch', intro: 'Busca productos por nombre o código de barras.' },
                        { element: '#dashboardCatalog', intro: 'Abre el catálogo público que ven tus clientes.' }
                    ],
                    nextLabel: 'Siguiente',
                    prevLabel: 'Anterior',
                    skipLabel: 'Saltar',
                    doneLabel: 'Listo',
                    showProgress: true,
                    showBullets: true,
                })
                .start();
        }

        const labels = @json($chartLabels);
        const values = @json($chartValues);
        const isDark = {{ $isLight ? 'false' : 'true' }};
        if (labels.length > 0 && document.getElementById('salesChart')) {
            new Chart(document.getElementById('salesChart'), {
                type: 'line',
                data: {
                    labels: labels.map(d => d.split('-')[2]),
                    datasets: [{
                        label: 'Ventas ($)',
                        data: values,
                        borderColor: '{{ $themeAccent }}',
                        backgroundColor: '{{ $themeAccent }}22',
                        fill: true,
                        tension: 0.3,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: isDark ? '#334155' : '#e5e7eb' }, ticks: { color: isDark ? '#cbd5e1' : '#374151' } },
                        x: { grid: { display: false }, ticks: { color: isDark ? '#cbd5e1' : '#374151' } }
                    }
                }
            });
        }
    </script>
</x-app-layout>
