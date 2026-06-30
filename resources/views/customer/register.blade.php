@extends('layouts.public')

@section('title', 'Registrarme como cliente - ' . \App\Models\Setting::get('business_name', config('app.name')))

@section('content')
<section class="hero">
    <div class="container text-center">
        <h1 class="display-5 fw-bold mb-3">Crear cuenta de cliente</h1>
        <p class="lead mb-0">Regístrate para hacer pedidos en línea de forma rápida.</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <form method="POST" action="{{ route('customer.register.store') }}" id="customer-register-form">
                            @csrf

                            <div class="mb-3">
                                <label for="name" class="form-label">Nombre completo <span class="text-danger">*</span></label>
                                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autofocus>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Correo electrónico <span class="text-danger">*</span></label>
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">Teléfono <span class="text-danger">*</span></label>
                                <input id="phone" type="tel" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone') }}" required>
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label for="default_address" class="form-label">Dirección de entrega / retiro <span class="text-danger">*</span></label>
                                <textarea id="default_address" class="form-control @error('default_address') is-invalid @enderror" name="default_address" rows="3" required>{{ old('default_address') }}</textarea>
                                @error('default_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña <span class="text-danger">*</span></label>
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required>
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label for="password_confirmation" class="form-label">Confirmar contraseña <span class="text-danger">*</span></label>
                                <input id="password_confirmation" type="password" class="form-control" name="password_confirmation" required>
                            </div>

                            <div class="mb-3 form-check">
                                <input class="form-check-input @error('terms') is-invalid @enderror" type="checkbox" id="terms" name="terms" value="1" required {{ old('terms') ? 'checked' : '' }}>
                                <label class="form-check-label" for="terms">
                                    Acepto los <a href="{{ route('legal.terms') }}" target="_blank">términos y condiciones</a> y la <a href="{{ route('legal.privacy') }}" target="_blank">política de privacidad</a>. <span class="text-danger">*</span>
                                </label>
                                @error('terms')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            @php($turnstileSiteKey = config('services.turnstile.site_key'))

                            @if($turnstileSiteKey)
                                <div class="mb-3">
                                    @error('turnstile')<div class="alert alert-danger">{{ $message }}</div>@enderror
                                    <div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}" data-callback="turnstileCallback"></div>
                                </div>
                            @endif

                            <button type="submit" class="btn btn-success btn-lg w-100" id="submit-btn" {{ $turnstileSiteKey ? 'disabled' : '' }}>
                                Crear cuenta
                            </button>

                            <p class="text-center mt-3 mb-0">
                                ¿Ya tienes cuenta? <a href="{{ route('login') }}">Iniciar sesión</a>
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
@if($turnstileSiteKey)
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <script>
        function turnstileCallback() {
            document.getElementById('submit-btn').disabled = false;
        }
    </script>
@endif
@endpush
