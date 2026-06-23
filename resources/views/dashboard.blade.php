<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Panel principal') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <p class="mb-0 fw-bold">Resumen del negocio</p>
                        <button id="dashboardTourBtn" class="btn btn-sm btn-outline-primary" onclick="startDashboardTour()">
                            <i class="bi bi-question-circle"></i> Iniciar tour
                        </button>
                    </div>

                    <div class="row g-3 mb-4" id="dashboardKpis">
                        <div class="col-md-3 col-sm-6">
                            <div class="card text-white bg-success h-100" id="kpiSalesToday">
                                <div class="card-body">
                                    <h6 class="card-title">Ventas hoy</h6>
                                    <p class="card-text fs-4 fw-bold">$ {{ number_format($salesToday, 2) }}</p>
                                    <small>{{ $ordersToday }} ordenes</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="card text-white bg-primary h-100" id="kpiSalesMonth">
                                <div class="card-body">
                                    <h6 class="card-title">Ventas del mes</h6>
                                    <p class="card-text fs-4 fw-bold">$ {{ number_format($salesMonth, 2) }}</p>
                                    <small>{{ $ordersMonth }} ordenes</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="card text-white bg-info h-100" id="kpiProducts">
                                <div class="card-body">
                                    <h6 class="card-title">Productos activos</h6>
                                    <p class="card-text fs-4 fw-bold">{{ $productsCount }}</p>
                                    <small>{{ $lowStock }} con stock bajo</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="card text-white bg-warning h-100" id="kpiPending">
                                <div class="card-body">
                                    <h6 class="card-title">Facturas por pagar</h6>
                                    <p class="card-text fs-4 fw-bold">{{ $pendingInvoices }}</p>
                                    <small>$ {{ number_format($pendingInvoiceAmount, 2) }}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($activeShift)
                        <div class="alert alert-success mb-4">
                            <i class="bi bi-check-circle"></i> Turno de caja abierto: <strong>{{ $activeShift->name }}</strong>
                            <a href="{{ route('pos.index') }}" class="btn btn-sm btn-success float-end">Ir al TPV</a>
                        </div>
                    @else
                        <div class="alert alert-warning mb-4">
                            <i class="bi bi-exclamation-triangle"></i> No hay turno de caja abierto. Abre uno antes de vender.
                            <a href="{{ route('pos.index') }}" class="btn btn-sm btn-warning float-end">Abrir caja</a>
                        </div>
                    @endif

                    <div class="row g-4 mb-4">
                        <div class="col-md-8">
                            <div class="card h-100" id="dashboardChart">
                                <div class="card-header fw-bold">Ventas del mes</div>
                                <div class="card-body">
                                    <canvas id="salesChart" height="120"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card h-100" id="dashboardLowStock">
                                <div class="card-header fw-bold">Stock bajo</div>
                                <ul class="list-group list-group-flush">
                                    @forelse($lowStockProducts as $product)
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>{{ Str::limit($product->name, 25) }}</span>
                                            <span class="badge bg-danger">{{ $product->stock_quantity }}</span>
                                        </li>
                                    @empty
                                        <li class="list-group-item text-muted">No hay productos con stock bajo.</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">
                    <p class="mb-3 fw-bold">Selecciona un módulo para continuar:</p>

                    @php($user = auth()->user())

                    <div id="dashboardTiles" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @if($user && $user->hasRole('admin'))
                            <a href="{{ route('pos.index') }}" class="block p-4 border rounded-md hover:bg-gray-100" id="dashboardPos">
                                <h3 class="font-semibold mb-1">TPV / Punto de venta</h3>
                                <p class="text-sm text-gray-600">Ventas rápidas y caja.</p>
                            </a>
                            <a href="{{ route('categories.index') }}" class="block p-4 border rounded-md hover:bg-gray-100" id="dashboardProducts">
                                <h3 class="font-semibold mb-1">Categorías y productos</h3>
                                <p class="text-sm text-gray-600">Gestiona productos y catálogo.</p>
                            </a>
                            <a href="{{ route('stock.index') }}" class="block p-4 border rounded-md hover:bg-gray-100" id="dashboardStock">
                                <h3 class="font-semibold mb-1">Inventario</h3>
                                <p class="text-sm text-gray-600">Ajustes, stock y movimientos.</p>
                            </a>
                            <a href="{{ route('warehouses.index') }}" class="block p-4 border rounded-md hover:bg-gray-100" id="dashboardWarehouses">
                                <h3 class="font-semibold mb-1">Depósitos</h3>
                                <p class="text-sm text-gray-600">Sitios de almacenamiento.</p>
                            </a>
                            <a href="{{ route('locations.index') }}" class="block p-4 border rounded-md hover:bg-gray-100" id="dashboardLocations">
                                <h3 class="font-semibold mb-1">Ubicaciones</h3>
                                <p class="text-sm text-gray-600">Pasillos, estantes, vitrinas.</p>
                            </a>
                            <a href="{{ route('catalog.index') }}" target="_blank" class="block p-4 border rounded-md hover:bg-gray-100" id="dashboardCatalog">
                                <h3 class="font-semibold mb-1">Catálogo público</h3>
                                <p class="text-sm text-gray-600">Ver tienda para clientes.</p>
                            </a>
                            <a href="{{ route('suppliers.index') }}" class="block p-4 border rounded-md hover:bg-gray-100" id="dashboardSuppliers">
                                <h3 class="font-semibold mb-1">Proveedores</h3>
                                <p class="text-sm text-gray-600">Gestiona compras y pagos.</p>
                            </a>
                            <a href="{{ route('employees.index') }}" class="block p-4 border rounded-md hover:bg-gray-100" id="dashboardEmployees">
                                <h3 class="font-semibold mb-1">Empleados</h3>
                                <p class="text-sm text-gray-600">Personal y nómina.</p>
                            </a>
                            <a href="{{ route('payroll-periods.index') }}" class="block p-4 border rounded-md hover:bg-gray-100" id="dashboardPayroll">
                                <h3 class="font-semibold mb-1">Nómina</h3>
                                <p class="text-sm text-gray-600">Períodos y procesos de nómina.</p>
                            </a>
                            <a href="{{ route('finances.index') }}" class="block p-4 border rounded-md hover:bg-gray-100" id="dashboardFinances">
                                <h3 class="font-semibold mb-1">Finanzas</h3>
                                <p class="text-sm text-gray-600">Gastos y categorías.</p>
                            </a>
                            <a href="{{ route('help.index') }}" class="block p-4 border rounded-md hover:bg-gray-100" id="dashboardHelp">
                                <h3 class="font-semibold mb-1">Centro de ayuda</h3>
                                <p class="text-sm text-gray-600">Tutoriales y guías.</p>
                            </a>
                            <a href="{{ route('settings.appearance.edit') }}" class="block p-4 border rounded-md hover:bg-gray-100" id="dashboardSettings">
                                <h3 class="font-semibold mb-1">Apariencia</h3>
                                <p class="text-sm text-gray-600">Logo, colores y fondo.</p>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script>
        function startDashboardTour() {
            if (typeof introJs === 'undefined') return;
            introJs()
                .setOptions({
                    steps: [
                        { element: '#dashboardKpis', intro: 'Aquí ves los indicadores clave: ventas hoy, ventas del mes, productos activos y facturas pendientes.' },
                        { element: '#dashboardChart', intro: 'Gráfica de ventas del mes para identificar tendencias.' },
                        { element: '#dashboardLowStock', intro: 'Productos que necesitan reabastecimiento.' },
                        { element: '#dashboardPos', intro: 'Desde aquí accedes al TPV para registrar ventas rápidas.' },
                        { element: '#dashboardProducts', intro: 'Crea y edita productos, categorías y precios.' },
                        { element: '#dashboardStock', intro: 'Gestiona stock, entradas, salidas y movimientos.' },
                        { element: '#dashboardWarehouses', intro: 'Primero define tus depósitos o almacenes.' },
                        { element: '#dashboardLocations', intro: 'Luego crea ubicaciones dentro de cada depósito: pasillo, estante, vitrina, cajón.' },
                        { element: '#dashboardCatalog', intro: 'Abre el catálogo público que ven tus clientes.' },
                        { element: '#dashboardSuppliers', intro: 'Administra proveedores y cuentas por pagar.' },
                        { element: '#dashboardHelp', intro: 'Si necesitas ayuda, revisa el centro de ayuda.' }
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
        if (labels.length > 0 && document.getElementById('salesChart')) {
            new Chart(document.getElementById('salesChart'), {
                type: 'line',
                data: {
                    labels: labels.map(d => d.split('-')[2]),
                    datasets: [{
                        label: 'Ventas ($)',
                        data: values,
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25, 135, 84, 0.1)',
                        fill: true,
                        tension: 0.3,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }
    </script>
</x-app-layout>
