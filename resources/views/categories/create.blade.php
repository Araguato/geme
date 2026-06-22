@extends('layout')

@section('content')
<h1>Nueva categoría</h1>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<form action="{{ route('categories.store') }}" method="POST" class="mt-3" enctype="multipart/form-data">
    @csrf
    <div class="mb-3">
        <label class="form-label">Nombre</label>
        <input type="text" name="name" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Descripción</label>
        <textarea name="description" class="form-control"></textarea>
    </div>
    <div class="mb-3">
        <label class="form-label">Área de preparación</label>
        <select name="prep_area" class="form-select">
            <option value="">Sin definir</option>
            <option value="cocina">Cocina</option>
            <option value="barra">Barra</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Imagen</label>
        <input type="file" name="image" class="form-control" accept="image/*">
        <div class="form-text">Opcional. Imagen para representar la categoría en el menú.</div>
    </div>
    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
        <label class="form-check-label" for="is_active">Activa</label>
    </div>
    <button type="submit" class="btn btn-primary">Guardar</button>
    <a href="{{ route('categories.index') }}" class="btn btn-secondary">Cancelar</a>
</form>
@endsection
