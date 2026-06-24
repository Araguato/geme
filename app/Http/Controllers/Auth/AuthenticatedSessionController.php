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

        $user = \Illuminate\Support\Facades\Auth::user();

        if ($user && method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            $request->session()->forget('url.intended');
            return redirect()->route('dashboard');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    protected function validateTurnstile(Request $request): void
    {
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
