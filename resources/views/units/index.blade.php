@extends('layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3" id="unitsHeader">
    <h1>Unidades</h1>
    <a href="{{ route('units.create') }}" class="btn btn-primary">Nueva unidad</a>
</div>

<table class="table table-striped" id="unitsTable">
    <thead>
    <tr>
        <th>Código</th>
        <th>Nombre</th>
        <th>Categoría</th>
        <th>Activa</th>
        <th>Acciones</th>
    </tr>
    </thead>
    <tbody>
    @foreach($units as $unit)
        <tr>
            <td>{{ $unit->code }}</td>
            <td>{{ $unit->name }}</td>
            <td>{{ $unit->category }}</td>
            <td>{{ $unit->is_active ? 'Sí' : 'No' }}</td>
            <td>
                <a href="{{ route('units.edit', $unit) }}" class="btn btn-sm btn-secondary">Editar</a>
                <form action="{{ route('units.destroy', $unit) }}" method="POST" style="display:inline-block" onsubmit="return confirm('¿Eliminar unidad?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                </form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
@endsection
