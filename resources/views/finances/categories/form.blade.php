@extends('layout')

@section('content')
    <h1>
        @if($mode === 'edit')
            Editar categoría de gasto
        @else
            Nueva categoría de gasto
        @endif
    </h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $mode === 'edit' ? route('finances.categories.update', $category) : route('finances.categories.store') }}" class="mt-3" style="max-width: 480px;">
        @csrf
        @if($mode === 'edit')
            @method('PUT')
        @endif

        <div class="mb-3">
            <label class="form-label">Nombre de la categoría</label>
            <input type="text" name="name" value="{{ old('name', $category->name) }}" class="form-control" required>
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $category->is_active) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">
                Categoría activa (disponible al registrar gastos)
            </label>
        </div>

        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="{{ route('finances.categories.index') }}" class="btn btn-secondary">Cancelar</a>
    </form>
@endsection
