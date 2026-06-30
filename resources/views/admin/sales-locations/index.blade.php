@extends('layout')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Ubicaciones de venta</h1>
        <a href="{{ route('sales-locations.create') }}" class="btn btn-primary">Nueva ubicación</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Código</th>
                <th>Dirección</th>
                <th>Teléfono</th>
                <th>Activo</th>
                <th class="text-end">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($locations as $location)
                <tr>
                    <td>{{ $location->name }}</td>
                    <td>{{ $location->code ?? '—' }}</td>
                    <td>{{ $location->address ?? '—' }}</td>
                    <td>{{ $location->phone ?? '—' }}</td>
                    <td>
                        @if($location->is_active)
                            <span class="badge bg-success">Sí</span>
                        @else
                            <span class="badge bg-secondary">No</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('sales-locations.edit', $location) }}" class="btn btn-sm btn-primary">Editar</a>
                        <form action="{{ route('sales-locations.destroy', $location) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar esta ubicación?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-muted">No hay ubicaciones de venta registradas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
