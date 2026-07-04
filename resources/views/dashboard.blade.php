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
    @endphp

    <div class="py-6 sm:py-8" style="background-color: {{ $pageBg }}; color: {{ $cardText }};">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold" style="color: {{ $cardText }};">Hola, {{ $user->name }}</h1>
                    <p class="text-sm mt-1" style="color: {{ $mutedText }};">Selecciona una acción para empezar.</p>
                </div>
                <button class="btn btn-sm btn-outline-primary hidden sm:inline-block" onclick="startDashboardTour()">
                    <i class="bi bi-question-circle"></i> Tour
                </button>
            </div>

            {{-- Estado de caja --}}
            <div class="card mb-4 shadow-sm border-0" style="background-color: {{ $cardBg }}; border: 1px solid {{ $borderColor }};">
                <div class="card-body d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        @if($activeShift)
                            <span class="badge bg-success fs-6"><i class="bi bi-check-circle"></i> Caja abierta</span>
                            <span class="fw-semibold" style="color: {{ $cardText }};">{{ $activeShift->name }}</span>
                        @else
                            <span class="badge bg-warning text-dark fs-6"><i class="bi bi-exclamation-triangle"></i> Caja cerrada</span>
                            <span style="color: {{ $cardText }};">Abre un turno antes de vender.</span>
                        @endif
                    </div>
                    <a href="{{ route('pos.index') }}" class="btn btn-primary w-100 w-sm-auto" style="background: {{ $themeAccent }}; border-color: {{ $themeAccent }}; color: {{ $isLight ? '#ffffff' : '#020617' }};">
                        <i class="bi bi-cart-check"></i> {{ $activeShift ? 'Ir al TPV' : 'Abrir caja' }}
                    </a>
                </div>
            </div>

            {{-- Módulos principales --}}
            <div class="mb-6">
                <h3 class="h5 mb-3 fw-semibold" style="color: {{ $cardText }};">Módulos</h3>
                <div class="row g-3">
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="{{ route('pos.index') }}" class="dashboard-tile card h-100 text-decoration-none border-0 shadow-sm" id="dashboardPos" style="background: {{ $cardBg }}; border: 1px solid {{ $borderColor }};">
                            <div class="card-body text-center py-4">
                                <div class="mb-2 d-flex justify-content-center align-items-center rounded-circle mx-auto" style="width: 48px; height: 48px; background: {{ $themeAccent }}22;">
                                    <i class="bi bi-cart-check fs-3" style="color: {{ $themeAccent }};"></i>
                                </div>
                                <h5 class="card-title mb-1" style="color: {{ $cardText }};">TPV</h5>
                                <p class="card-text small mb-0" style="color: {{ $mutedText }};">Punto de venta</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="{{ route('categories.index') }}" class="dashboard-tile card h-100 text-decoration-none border-0 shadow-sm" id="dashboardProducts" style="background: {{ $cardBg }}; border: 1px solid {{ $borderColor }};">
                            <div class="card-body text-center py-4">
                                <div class="mb-2 d-flex justify-content-center align-items-center rounded-circle mx-auto" style="width: 48px; height: 48px; background: {{ $themeAccent }}22;">
                                    <i class="bi bi-box-seam fs-3" style="color: {{ $themeAccent }};"></i>
                                </div>
                                <h5 class="card-title mb-1" style="color: {{ $cardText }};">Productos</h5>
                                <p class="card-text small mb-0" style="color: {{ $mutedText }};">Catálogo y precios</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="{{ route('stock.index') }}" class="dashboard-tile card h-100 text-decoration-none border-0 shadow-sm" id="dashboardStock" style="background: {{ $cardBg }}; border: 1px solid {{ $borderColor }};">
                            <div class="card-body text-center py-4">
                                <div class="mb-2 d-flex justify-content-center align-items-center rounded-circle mx-auto" style="width: 48px; height: 48px; background: {{ $themeAccent }}22;">
                                    <i class="bi bi-clipboard-data fs-3" style="color: {{ $themeAccent }};"></i>
                                </div>
                                <h5 class="card-title mb-1" style="color: {{ $cardText }};">Inventario</h5>
                                <p class="card-text small mb-0" style="color: {{ $mutedText }};">Stock y ajustes</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="{{ route('products.search') }}" class="dashboard-tile card h-100 text-decoration-none border-0 shadow-sm" id="dashboardSearch" style="background: {{ $cardBg }}; border: 1px solid {{ $borderColor }};">
                            <div class="card-body text-center py-4">
                                <div class="mb-2 d-flex justify-content-center align-items-center rounded-circle mx-auto" style="width: 48px; height: 48px; background: {{ $themeAccent }}22;">
                                    <i class="bi bi-upc-scan fs-3" style="color: {{ $themeAccent }};"></i>
                                </div>
                                <h5 class="card-title mb-1" style="color: {{ $cardText }};">Buscar</h5>
                                <p class="card-text small mb-0" style="color: {{ $mutedText }};">Por barcode o nombre</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="{{ route('warehouses.index') }}" class="dashboard-tile card h-100 text-decoration-none border-0 shadow-sm" id="dashboardWarehouses" style="background: {{ $cardBg }}; border: 1px solid {{ $borderColor }};">
                            <div class="card-body text-center py-4">
                                <div class="mb-2 d-flex justify-content-center align-items-center rounded-circle mx-auto" style="width: 48px; height: 48px; background: {{ $themeAccent }}22;">
                                    <i class="bi bi-building fs-3" style="color: {{ $themeAccent }};"></i>
                                </div>
                                <h5 class="card-title mb-1" style="color: {{ $cardText }};">Depósitos</h5>
                                <p class="card-text small mb-0" style="color: {{ $mutedText }};">Almacenes</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="{{ route('locations.index') }}" class="dashboard-tile card h-100 text-decoration-none border-0 shadow-sm" id="dashboardLocations" style="background: {{ $cardBg }}; border: 1px solid {{ $borderColor }};">
                            <div class="card-body text-center py-4">
                                <div class="mb-2 d-flex justify-content-center align-items-center rounded-circle mx-auto" style="width: 48px; height: 48px; background: {{ $themeAccent }}22;">
                                    <i class="bi bi-geo-alt fs-3" style="color: {{ $themeAccent }};"></i>
                                </div>
                                <h5 class="card-title mb-1" style="color: {{ $cardText }};">Ubicaciones</h5>
                                <p class="card-text small mb-0" style="color: {{ $mutedText }};">Pasillos y estantes</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="{{ route('catalog.index') }}" target="_blank" class="dashboard-tile card h-100 text-decoration-none border-0 shadow-sm" id="dashboardCatalog" style="background: {{ $cardBg }}; border: 1px solid {{ $borderColor }};">
                            <div class="card-body text-center py-4">
                                <div class="mb-2 d-flex justify-content-center align-items-center rounded-circle mx-auto" style="width: 48px; height: 48px; background: {{ $themeAccent }}22;">
                                    <i class="bi bi-shop fs-3" style="color: {{ $themeAccent }};"></i>
                                </div>
                                <h5 class="card-title mb-1" style="color: {{ $cardText }};">Catálogo</h5>
                                <p class="card-text small mb-0" style="color: {{ $mutedText }};">Tienda pública</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="{{ route('help.index') }}" class="dashboard-tile card h-100 text-decoration-none border-0 shadow-sm" id="dashboardHelp" style="background: {{ $cardBg }}; border: 1px solid {{ $borderColor }};">
                            <div class="card-body text-center py-4">
                                <div class="mb-2 d-flex justify-content-center align-items-center rounded-circle mx-auto" style="width: 48px; height: 48px; background: {{ $themeAccent }}22;">
                                    <i class="bi bi-question-circle fs-3" style="color: {{ $themeAccent }};"></i>
                                </div>
                                <h5 class="card-title mb-1" style="color: {{ $cardText }};">Ayuda</h5>
                                <p class="card-text small mb-0" style="color: {{ $mutedText }};">Tutoriales</p>
                            </div>
                        </a>
                    </div>
                    @if($isAdmin)
                        <div class="col-6 col-md-4 col-lg-3">
                            <a href="{{ route('suppliers.index') }}" class="dashboard-tile card h-100 text-decoration-none border-0 shadow-sm" id="dashboardSuppliers" style="background: {{ $cardBg }}; border: 1px solid {{ $borderColor }};">
                                <div class="card-body text-center py-4">
                                    <div class="mb-2 d-flex justify-content-center align-items-center rounded-circle mx-auto" style="width: 48px; height: 48px; background: {{ $themeAccent }}22;">
                                        <i class="bi bi-truck fs-3" style="color: {{ $themeAccent }};"></i>
                                    </div>
                                    <h5 class="card-title mb-1" style="color: {{ $cardText }};">Proveedores</h5>
                                    <p class="card-text small mb-0" style="color: {{ $mutedText }};">Compras y pagos</p>
                                </div>
                            </a>
                        </div>
                        <div class="col-6 col-md-4 col-lg-3">
                            <a href="{{ route('employees.index') }}" class="dashboard-tile card h-100 text-decoration-none border-0 shadow-sm" id="dashboardEmployees" style="background: {{ $cardBg }}; border: 1px solid {{ $borderColor }};">
                                <div class="card-body text-center py-4">
                                    <div class="mb-2 d-flex justify-content-center align-items-center rounded-circle mx-auto" style="width: 48px; height: 48px; background: {{ $themeAccent }}22;">
                                        <i class="bi bi-people fs-3" style="color: {{ $themeAccent }};"></i>
                                    </div>
                                    <h5 class="card-title mb-1" style="color: {{ $cardText }};">Empleados</h5>
                                    <p class="card-text small mb-0" style="color: {{ $mutedText }};">Personal y nómina</p>
                                </div>
                            </a>
                        </div>
                        <div class="col-6 col-md-4 col-lg-3">
                            <a href="{{ route('payroll-periods.index') }}" class="dashboard-tile card h-100 text-decoration-none border-0 shadow-sm" id="dashboardPayroll" style="background: {{ $cardBg }}; border: 1px solid {{ $borderColor }};">
                                <div class="card-body text-center py-4">
                                    <div class="mb-2 d-flex justify-content-center align-items-center rounded-circle mx-auto" style="width: 48px; height: 48px; background: {{ $themeAccent }}22;">
                                        <i class="bi bi-wallet2 fs-3" style="color: {{ $themeAccent }};"></i>
                                    </div>
                                    <h5 class="card-title mb-1" style="color: {{ $cardText }};">Nómina</h5>
                                    <p class="card-text small mb-0" style="color: {{ $mutedText }};">Períodos y procesos</p>
                                </div>
                            </a>
                        </div>
                        <div class="col-6 col-md-4 col-lg-3">
                            <a href="{{ route('finances.index') }}" class="dashboard-tile card h-100 text-decoration-none border-0 shadow-sm" id="dashboardFinances" style="background: {{ $cardBg }}; border: 1px solid {{ $borderColor }};">
                                <div class="card-body text-center py-4">
                                    <div class="mb-2 d-flex justify-content-center align-items-center rounded-circle mx-auto" style="width: 48px; height: 48px; background: {{ $themeAccent }}22;">
                                        <i class="bi bi-graph-up fs-3" style="color: {{ $themeAccent }};"></i>
                                    </div>
                                    <h5 class="card-title mb-1" style="color: {{ $cardText }};">Finanzas</h5>
                                    <p class="card-text small mb-0" style="color: {{ $mutedText }};">Gastos y reportes</p>
                                </div>
                            </a>
                        </div>
                        <div class="col-6 col-md-4 col-lg-3">
                            <a href="{{ route('settings.appearance.edit') }}" class="dashboard-tile card h-100 text-decoration-none border-0 shadow-sm" id="dashboardSettings" style="background: {{ $cardBg }}; border: 1px solid {{ $borderColor }};">
                                <div class="card-body text-center py-4">
                                    <div class="mb-2 d-flex justify-content-center align-items-center rounded-circle mx-auto" style="width: 48px; height: 48px; background: {{ $themeAccent }}22;">
                                        <i class="bi bi-palette fs-3" style="color: {{ $themeAccent }};"></i>
                                    </div>
                                    <h5 class="card-title mb-1" style="color: {{ $cardText }};">Apariencia</h5>
                                    <p class="card-text small mb-0" style="color: {{ $mutedText }};">Colores y logo</p>
                                </div>
                            </a>
                        </div>
                    @endif
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
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .dashboard-tile:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
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
