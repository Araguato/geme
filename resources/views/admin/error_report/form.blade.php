@extends('layout')

@section('content')
    <h1>Reportar problema del sistema</h1>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('error-report.store') }}" method="POST" class="mt-3">
        @csrf

        <input type="hidden" name="url" value="{{ $currentUrl }}">

        <div class="mb-3">
            <label class="form-label">Título del problema *</label>
            <input type="text" name="subject" class="form-control" required
                   placeholder="Ej: Error al cerrar una cuenta" value="{{ old('subject') }}">
        </div>

        <div class="mb-3">
            <label class="form-label">Descripción *</label>
            <textarea name="description" class="form-control" rows="5" required
                      placeholder="Explica qué estabas haciendo, qué esperabas que pasara y qué pasó realmente.">{{ old('description') }}</textarea>
        </div>

        <p class="text-muted small">
            Se enviará también información técnica básica (usuario, URL, IP, navegador) para ayudar a diagnosticar el problema.
        </p>

        <button type="submit" class="btn btn-warning">Enviar reporte</button>
        <a href="{{ url()->previous() }}" class="btn btn-secondary">Cancelar</a>
    </form>
@endsection
