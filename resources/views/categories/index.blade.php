@extends('layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3" id="categoriesHeader">
    <h1 id="categoriesTitle">Categorías</h1>
    <a href="{{ route('categories.create') }}" class="btn btn-primary" id="categoriesCreateBtn">Nueva categoría</a>
</div>
<table class="table table-striped" id="categoriesTable">
    <thead>
    <tr>
        <th>Imagen</th>
        <th>Nombre</th>
        <th>Descripción</th>
        <th>Activa</th>
        <th>Acciones</th>
    </tr>
    </thead>
    <tbody>
    @foreach($categories as $category)
        <tr>
            <td>
                @if($category->image_path)
                    <img src="{{ asset('storage/' . $category->image_path) }}" alt="{{ $category->image_path }}" style="max-height: 50px;">
                @else
                    -
                @endif
            </td>
            <td>{{ $category->name }}</td>
            <td>{{ $category->description }}</td>
            <td>{{ $category->is_active ? 'Sí' : 'No' }}</td>
            <td>
                <a href="{{ route('categories.edit', $category) }}" class="btn btn-sm btn-secondary">Editar</a>
                <form action="{{ route('categories.destroy', $category) }}" method="POST" style="display:inline-block" onsubmit="return confirm('¿Eliminar categoría?')">
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
    window.GEME_TOUR_STEPS = [
        {
            intro: 'Aquí administras las categorías del catálogo.'
        },
        {
            element: '#categoriesCreateBtn',
            intro: 'Crea una nueva categoría para organizar tus productos.'
        },
        {
            element: '#categoriesTable',
            intro: 'Listado de categorías. Desde aquí puedes editar o eliminar.'
        }
    ];
</script>
@endsection
