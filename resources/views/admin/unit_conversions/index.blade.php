@extends('layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Conversiones de unidades</h1>
    <a href="{{ route('unit-conversions.create') }}" class="btn btn-primary">Nueva conversión</a>
</div>

<table class="table table-striped">
    <thead>
        <tr>
            <th>Desde</th>
            <th>Hacia</th>
            <th>Factor</th>
            <th>Activa</th>
            <th class="text-end">Acciones</th>
        </tr>
    </thead>
    <tbody>
    @forelse($conversions as $conversion)
        <tr>
            <td>{{ $conversion->fromUnit?->name }} ({{ $conversion->fromUnit?->code }})</td>
            <td>{{ $conversion->toUnit?->name }} ({{ $conversion->toUnit?->code }})</td>
            <td>{{ $conversion->factor }}</td>
            <td>{{ $conversion->is_active ? 'Sí' : 'No' }}</td>
            <td class="text-end">
                <a href="{{ route('unit-conversions.edit', $conversion) }}" class="btn btn-sm btn-secondary">Editar</a>
                <form action="{{ route('unit-conversions.destroy', $conversion) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar esta conversión?')">Eliminar</button>
                </form>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="5" class="text-center">No hay conversiones definidas.</td>
        </tr>
    @endforelse
    </tbody>
</table>
@endsection
