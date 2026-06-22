@extends('layout')

@section('content')
<h1 class="h3 mb-3">Nuevo usuario</h1>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('users.store') }}" method="POST" class="mt-3">
    @csrf
    <div class="mb-3">
        <label class="form-label">Nombre</label>
        <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" value="{{ old('email') }}" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">PIN (opcional)</label>
        <input type="text" name="pin" value="{{ old('pin') }}" class="form-control" maxlength="10">
        <div class="form-text">PIN corto para identificarse en TPV / Despachador / Caja.</div>
    </div>
    <div class="mb-3">
        <label class="form-label">Contraseña</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Confirmar contraseña</label>
        <input type="password" name="password_confirmation" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Roles</label>
        <div class="row">
            @foreach($roles as $role)
                <div class="col-12 col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->id }}" id="role_{{ $role->id }}">
                        <label class="form-check-label" for="role_{{ $role->id }}">
                            {{ $role->name === 'mesonero' ? 'despachador' : $role->name }} <span class="text-muted">- {{ $role->description }}</span>
                        </label>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    <button type="submit" class="btn btn-primary">Guardar</button>
    <a href="{{ route('users.index') }}" class="btn btn-link">Cancelar</a>
</form>
@endsection
