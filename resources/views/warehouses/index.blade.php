@extends('layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Sitios / Depósitos</h1>
    <a href="{{ route('warehouses.create') }}" class="btn btn-primary">Nuevo depósito</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<table class="table table-striped">
    <thead>
        <tr>
            <th>Código</th>
            <th>Nombre</th>
            <th>Dirección</th>
            <th>Activo</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        @foreach($warehouses as $warehouse)
            <tr>
                <td>{{ $warehouse->code }}</td>
                <td>{{ $warehouse->name }}</td>
                <td>{{ $warehouse->address ?? '-' }}</td>
                <td>{{ $warehouse->is_active ? 'Sí' : 'No' }}</td>
                <td>
                    <a href="{{ route('warehouses.edit', $warehouse) }}" class="btn btn-sm btn-secondary">Editar</a>
                    <form action="{{ route('warehouses.destroy', $warehouse) }}" method="POST" style="display:inline-block" onsubmit="return confirm('¿Eliminar depósito?')">
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
