<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class CustomerRegistrationController extends Controller
{
    public function create()
    {
        return view('customer.register');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['required', 'string', 'max:50'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'default_address' => ['required', 'string', 'max:500'],
            'terms' => ['required', 'accepted'],
        ]);

        $this->validateTurnstile($request);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'default_address' => $request->default_address,
            'password' => Hash::make($request->password),
            'terms_accepted_at' => now(),
        ]);

        $clientRole = Role::firstOrCreate(
            ['name' => 'cliente'],
            ['description' => 'Cliente registrado']
        );
        $user->roles()->attach($clientRole->id);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('public.order.index')
            ->with('success', __('Tu cuenta fue creada. Ya puedes hacer tu pedido.'));
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
            // Si no está configurada la clave secreta, permitimos en desarrollo
            // pero rechazamos en producción para evitar registros sin protección.
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

        $body = $response->json();

        if (!($body['success'] ?? false)) {
            throw ValidationException::withMessages([
                'turnstile' => __('La verificación de seguridad falló. Intenta de nuevo.'),
            ]);
        }
    }
}
