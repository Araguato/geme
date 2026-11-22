<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $this->validateTurnstile($request);

        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        // Cada rol entra a su pantalla de trabajo (TPV, almacén, finanzas, panel).
        // El admin siempre va al panel; el resto vuelve a la página que intentaba abrir.
        if ($user->hasRole('admin')) {
            $request->session()->forget('url.intended');
        }

        return redirect()->intended(route($user->homeRouteName(), absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        // Pide al navegador vaciar su caché de este sitio (páginas vistas durante la sesión)
        return redirect('/')->header('Clear-Site-Data', '"cache"');
    }

    protected function validateTurnstile(Request $request): void
    {
        if (app()->environment('local', 'testing')) {
            return;
        }

        $token = $request->input('cf-turnstile-response');

        if (empty($token)) {
            throw ValidationException::withMessages([
                'turnstile' => __('Debes completar la verificación de seguridad.'),
            ]);
        }

        $secret = config('services.turnstile.secret');

        if (empty($secret)) {
            if (app()->environment('production')) {
                throw ValidationException::withMessages([
                    'turnstile' => __('La verificación de seguridad no está configurada.'),
                ]);
            }
            return;
        }

        $response = Http::asForm()
            ->timeout(10)
            ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);

        if (!($response->json('success') ?? false)) {
            throw ValidationException::withMessages([
                'turnstile' => __('La verificación de seguridad falló. Intenta de nuevo.'),
            ]);
        }
    }
}
