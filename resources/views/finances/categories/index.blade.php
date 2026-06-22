@extends('layout')

@section('content')
    <h1>Categorías de gastos</h1>
    <p class="text-muted">Define las categorías para tus gastos de negocio y consumos privados.</p>

    <div class="mb-3 d-flex justify-content-between">
        <a href="{{ route('finances.index') }}" class="btn btn-secondary">⟵ Volver a finanzas</a>
        <a href="{{ route('finances.categories.create') }}" class="btn btn-success">Nueva categoría</a>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-sm align-middle">
            <thead>
            <tr>
                <th>Nombre</th>
                <th>Activa</th>
                <th style="width: 140px;"></th>
            </tr>
            </thead>
            <tbody>
            @forelse($categories as $category)
                <tr>
                    <td>{{ $category->name }}</td>
                    <td>
                        @if($category->is_active)
                            <span class="badge bg-success">Sí</span>
                        @else
                            <span class="badge bg-secondary">No</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('finances.categories.edit', $category) }}" class="btn btn-sm btn-primary">Editar</a>
                        <form action="{{ route('finances.categories.destroy', $category) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('¿Eliminar esta categoría? Los gastos existentes seguirán referenciando esta categoría.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">X</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center text-muted">Keine Kategorien vorhanden.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
