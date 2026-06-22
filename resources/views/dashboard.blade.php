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
                    <p class="mb-4">Selecciona un módulo para continuar:</p>

                    @php($user = auth()->user())

                    <div id="dashboardTiles" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @if($user && $user->hasRole('admin'))
                            <a href="{{ route('categories.index') }}" class="block p-4 border rounded-md hover:bg-gray-100">
                                <h3 class="font-semibold mb-1">Categorías y productos</h3>
                                <p class="text-sm text-gray-600">Gestiona productos y catálogo.</p>
                            </a>
                            <a href="{{ route('stock.index') }}" class="block p-4 border rounded-md hover:bg-gray-100">
                                <h3 class="font-semibold mb-1">Inventario</h3>
                                <p class="text-sm text-gray-600">Ajustes, stock y movimientos.</p>
                            </a>
                            <a href="{{ route('suppliers.index') }}" class="block p-4 border rounded-md hover:bg-gray-100">
                                <h3 class="font-semibold mb-1">Proveedores</h3>
                                <p class="text-sm text-gray-600">Gestiona compras y pagos.</p>
                            </a>
                            <a href="{{ route('employees.index') }}" class="block p-4 border rounded-md hover:bg-gray-100">
                                <h3 class="font-semibold mb-1">Empleados</h3>
                                <p class="text-sm text-gray-600">Personal y nómina.</p>
                            </a>
                            <a href="{{ route('payroll-periods.index') }}" class="block p-4 border rounded-md hover:bg-gray-100">
                                <h3 class="font-semibold mb-1">Nómina</h3>
                                <p class="text-sm text-gray-600">Períodos y procesos de nómina.</p>
                            </a>
                            <a href="{{ route('finances.index') }}" class="block p-4 border rounded-md hover:bg-gray-100">
                                <h3 class="font-semibold mb-1">Finanzas</h3>
                                <p class="text-sm text-gray-600">Gastos y categorías.</p>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
