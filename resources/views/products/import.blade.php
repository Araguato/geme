@extends('layout')

@section('content')
<h1>Importar productos desde CSV</h1>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="mb-3">
    <p>Sube un archivo CSV con la misma estructura que el export generado por "Exportar CSV".</p>
    <ul>
        <li>Debe incluir las columnas: <code>id, category_name, name, description, price, is_active, prep_area, image_path</code>.</li>
        <li>Si <code>id</code> está vacío, se creará un producto nuevo.</li>
        <li>Si <code>id</code> tiene valor y existe, el producto será actualizado.</li>
        <li>Las categorías se resuelven por <code>category_name</code> (se crean si no existen).</li>
    </ul>
</div>

<form action="{{ route('products.import') }}" method="POST" enctype="multipart/form-data" class="card p-3">
    @csrf
    <div class="mb-3">
        <label for="file" class="form-label">Archivo CSV</label>
        <input type="file" name="file" id="file" class="form-control" accept=".csv,text/csv">
    </div>
    <button type="submit" class="btn btn-primary">Importar</button>
    <a href="{{ route('products.index') }}" class="btn btn-link">Cancelar</a>
</form>
@endsection
