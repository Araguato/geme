@extends('layout')

@section('content')
<h1>Editar categoría</h1>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<form action="{{ route('categories.update', $category) }}" method="POST" class="mt-3" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="mb-3">
        <label class="form-label">Nombre</label>
        <input type="text" name="name" class="form-control" value="{{ $category->name }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Descripción</label>
        <textarea name="description" class="form-control">{{ $category->description }}</textarea>
    </div>
    <div class="mb-3">
        <label class="form-label">Área de preparación</label>
        <select name="prep_area" class="form-select">
            <option value="" {{ $category->prep_area === null ? 'selected' : '' }}>Sin definir</option>
            <option value="cocina" {{ $category->prep_area === 'cocina' ? 'selected' : '' }}>Cocina</option>
            <option value="barra" {{ $category->prep_area === 'barra' ? 'selected' : '' }}>Barra</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Imagen</label>
        @if($category->image_path)
            <div class="mb-2">
                <img src="{{ asset('storage/' . $category->image_path) }}" alt="Imagen actual" style="max-height: 80px;">
            </div>
        @endif
        <input type="file" name="image" class="form-control" accept="image/*">
        <div class="form-text">Opcional. Si subes una nueva imagen, reemplazará a la anterior.</div>
    </div>
    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" {{ $category->is_active ? 'checked' : '' }}>
        <label class="form-check-label" for="is_active">Activa</label>
    </div>
    <button type="submit" class="btn btn-primary">Actualizar</button>
    <a href="{{ route('categories.index') }}" class="btn btn-secondary">Cancelar</a>
</form>
@endsection
