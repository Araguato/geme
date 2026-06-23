@extends('layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Ubicaciones</h1>
    <div>
        <button class="btn btn-sm btn-outline-secondary me-2" onclick="startLocationsTour()">
            <i class="bi bi-question-circle"></i> Tour
        </button>
        <a href="{{ route('locations.create') }}" class="btn btn-primary" id="locationsCreateBtn">Nueva ubicación</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<table class="table table-striped" id="locationsTable">
    <thead>
        <tr>
            <th>Depósito</th>
            <th>Código</th>
            <th>Nombre</th>
            <th>Detalles</th>
            <th>Activo</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        @foreach($locations as $location)
            <tr>
                <td>{{ $location->warehouse?->name ?? '—' }}</td>
                <td>{{ $location->code }}</td>
                <td>{{ $location->name }}</td>
                <td>
                    <small class="text-muted">
                        {{ $location->aisle ? 'P '.$location->aisle : '' }}
                        {{ $location->shelf ? 'E '.$location->shelf : '' }}
                        {{ $location->rack ? 'A '.$location->rack : '' }}
                        {{ $location->bin ? 'C '.$location->bin : '' }}
                        {{ $location->section ? 'V '.$location->section : '' }}
                    </small>
                </td>
                <td>{{ $location->is_active ? 'Sí' : 'No' }}</td>
                <td>
                    <a href="{{ route('locations.edit', $location) }}" class="btn btn-sm btn-secondary">Editar</a>
                    <form action="{{ route('locations.destroy', $location) }}" method="POST" style="display:inline-block" onsubmit="return confirm('¿Eliminar ubicación?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

<script>
    window.WAWI_TOUR_STEPS = [
        {
            intro: 'Aquí administras las ubicaciones físicas dentro de cada depósito. Definirlas bien evita que los productos se "pierdan" por tipeos como "Bitrina" en lugar de "Vitrina".'
        },
        {
            element: '#locationsCreateBtn',
            intro: 'Crea una ubicación por cada combinación que uses: pasillo, estante, anaquel, cajón o vitrina.'
        },
        {
            element: '#locationsTable',
            intro: 'Listado de ubicaciones. Al crear productos solo podrás elegir ubicaciones de esta lista.'
        }
    ];

    function startLocationsTour() {
        if (typeof introJs === 'undefined') return;
        introJs()
            .setOptions({
                steps: window.WAWI_TOUR_STEPS,
                nextLabel: 'Siguiente',
                prevLabel: 'Anterior',
                skipLabel: 'Saltar',
                doneLabel: 'Listo',
                showProgress: true,
                showBullets: true,
            })
            .start();
    }
</script>
@endsection
