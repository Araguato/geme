@extends('layout')

@section('content')
    <h1>Configuración de finanzas</h1>
    <p class="text-muted">Solo administrador: activa o desactiva el módulo de finanzas (gastos y consumos privados).</p>

    <form action="{{ route('settings.finances.update') }}" method="POST" class="mt-3" style="max-width: 520px;">
        @csrf

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="finances_enabled" id="finances_enabled" value="1" {{ $financesEnabled ? 'checked' : '' }}>
            <label class="form-check-label" for="finances_enabled">
                Módulo de finanzas (gastos y consumos) activado
            </label>
        </div>

        <button type="submit" class="btn btn-primary">Guardar configuración</button>
    </form>
@endsection
