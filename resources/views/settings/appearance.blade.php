@extends('layout')

@section('content')
    <h1>Configuración de apariencia</h1>
    <p class="text-muted">Solo administrador: ajusta colores, tamaño de letra y fondo del sistema.</p>

    <form action="{{ route('settings.appearance.update') }}" method="POST" enctype="multipart/form-data" class="mt-3" style="max-width: 500px;">
        @csrf

        <div class="mb-3">
            <label class="form-label">Nombre del negocio</label>
            <input type="text" name="business_name" class="form-control" value="{{ old('business_name', $businessName) }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Esquema de colores</label>
            <select name="theme_variant" class="form-select" required>
                @php
                    $currentVariant = old('theme_variant', $themeVariant ?? 'classic');
                @endphp
                <option value="classic" {{ $currentVariant === 'classic' ? 'selected' : '' }}>geme (oscuro)</option>
                <option value="light" {{ $currentVariant === 'light' ? 'selected' : '' }}>Claro neutro</option>
                <option value="dark" {{ $currentVariant === 'dark' ? 'selected' : '' }}>Oscuro gris</option>
                <option value="vibrant" {{ $currentVariant === 'vibrant' ? 'selected' : '' }}>Clásico alegre (rojo / dorado)</option>
                <option value="custom" {{ $currentVariant === 'custom' ? 'selected' : '' }}>Personalizado (usar colores de abajo)</option>
            </select>
            <div class="form-text">El esquema define una paleta base. En modo "Personalizado" se usan los colores que configures debajo.</div>
        </div>

        <div class="mb-3">
            <label class="form-label">Color principal</label>
            <div class="d-flex gap-2 align-items-center">
                <input type="color" name="theme_primary_color" class="form-control form-control-color" value="{{ old('theme_primary_color', $themePrimaryColor) }}" required style="max-width: 4rem;">
                <input type="text" class="form-control" value="{{ old('theme_primary_color', $themePrimaryColor) }}" disabled>
            </div>
            <div class="form-text">Usado en la barra superior y botones principales.</div>
        </div>

        <div class="mb-3">
            <label class="form-label">Color de acento</label>
            <div class="d-flex gap-2 align-items-center">
                <input type="color" name="theme_accent_color" class="form-control form-control-color" value="{{ old('theme_accent_color', $themeAccentColor) }}" required style="max-width: 4rem;">
                <input type="text" class="form-control" value="{{ old('theme_accent_color', $themeAccentColor) }}" disabled>
            </div>
            <div class="form-text">Usado para detalles, bordes y resaltados.</div>
        </div>

        <div class="mb-3">
            <label class="form-label">Tamaño base de letra</label>
            <select name="theme_font_scale" class="form-select" required>
                @php
                    $fontOptions = [
                        1.0 => 'Normal',
                        1.1 => 'Un poco más grande',
                        1.2 => 'Grande',
                    ];
                @endphp
                @foreach($fontOptions as $scale => $label)
                    <option value="{{ $scale }}" {{ (float) old('theme_font_scale', $themeFontScale) == (float) $scale ? 'selected' : '' }}>
                        {{ $label }} ({{ $scale }}x)
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Fondo</label>
            <select name="theme_background_mode" class="form-select" required>
                <option value="gradient" {{ old('theme_background_mode', $themeBackgroundMode) === 'gradient' ? 'selected' : '' }}>Degradado rojo/dorado</option>
                <option value="image" {{ old('theme_background_mode', $themeBackgroundMode) === 'image' ? 'selected' : '' }}>Imagen de fondo (por URL)</option>
            </select>
            <div class="form-text">Si eliges imagen, indica la URL accesible desde el servidor (puede ser un archivo en /storage o una URL externa).</div>
        </div>

        <div class="mb-3">
            <label class="form-label">Imagen de fondo</label>
            <input type="file" name="theme_background_image_file" class="form-control" accept="image/jpg,image/jpeg,image/png,image/webp">
            <div class="form-text">Puedes subir una imagen desde tu equipo. Si prefieres usar una URL manual, déjalo vacío y usa el campo de abajo.</div>
        </div>

        <div class="mb-3">
            <label class="form-label">URL de imagen de fondo (opcional)</label>
            <input type="text" name="theme_background_image_url" class="form-control" value="{{ old('theme_background_image_url', $themeBackgroundImageUrl) }}">
            <div class="form-text">Solo se usa si no se sube un archivo nuevo.</div>
        </div>

        <div class="mb-3">
            <label class="form-label">Logo</label>
            <input type="file" name="theme_logo_file" class="form-control" accept="image/jpg,image/jpeg,image/png,image/webp">
            <div class="form-text">Puedes subir un archivo de logo. Si prefieres una URL existente, usa el campo de abajo.</div>
        </div>

        <div class="mb-3">
            <label class="form-label">URL de logo (opcional)</label>
            <input type="text" name="theme_logo_url" class="form-control" value="{{ old('theme_logo_url', $themeLogoUrl) }}">
            <div class="form-text">Se usará solo si no se sube un archivo de logo.</div>
        </div>

        <button type="submit" class="btn btn-primary">Guardar apariencia</button>
    </form>

    <p class="mt-3 small text-muted">
        Valores actuales usados por el tema:<br>
        Nombre: <strong>{{ $businessName }}</strong><br>
        Fondo: <code>{{ $themeBackgroundImageUrl ?: 'sin fondo personalizado' }}</code><br>
        Logo: <code>{{ $themeLogoUrl ?: 'sin logo personalizado' }}</code>
    </p>
@endsection
