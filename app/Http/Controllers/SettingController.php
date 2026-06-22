<?php

namespace App\Http\Controllers;

use App\Models\ExchangeRate;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    protected function authorizeAdmin(): void
    {
        $user = auth()->user();
        if (!$user || !method_exists($user, 'hasRole') || !$user->hasRole('admin')) {
            abort(403);
        }
    }

    public function editBcv()
    {
        $this->authorizeAdmin();

        $bcvRate = Setting::get('bcv_rate');

        $rates = ExchangeRate::query()
            ->orderByDesc('rate_date')
            ->limit(90)
            ->get(['rate_date', 'bs_per_usd'])
            ->reverse()
            ->values();

        return view('settings.bcv', compact('bcvRate', 'rates'));
    }

    public function updateBcv(Request $request)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'bcv_rate' => 'required|numeric|min:0.0001',
        ]);

        Setting::set('bcv_rate', $data['bcv_rate']);

        ExchangeRate::updateOrCreate(
            ['rate_date' => now()->toDateString()],
            [
                'bs_per_usd' => $data['bcv_rate'],
                'source' => 'manual',
                'created_by' => auth()->id(),
            ]
        );

        return redirect()->route('settings.bcv.edit');
    }

    public function editAppearance()
    {
        $this->authorizeAdmin();

        $themePrimaryColor = Setting::get('theme_primary_color', '#0f172a');
        $themeAccentColor = Setting::get('theme_accent_color', '#22c55e');
        $themeFontScale = Setting::get('theme_font_scale', '1.05');
        $themeBackgroundMode = Setting::get('theme_background_mode', 'gradient');
        $themeBackgroundImageUrl = Setting::get('theme_background_image_url', '');
        $themeLogoUrl = Setting::get('theme_logo_url', '');
        $businessName = Setting::get('business_name', 'WAWI');
        $themeVariant = Setting::get('theme_variant', 'classic');

        return view('settings.appearance', compact(
            'themePrimaryColor',
            'themeAccentColor',
            'themeFontScale',
            'themeBackgroundMode',
            'themeBackgroundImageUrl',
            'themeLogoUrl',
            'businessName',
            'themeVariant'
        ));
    }

    public function updateAppearance(Request $request)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'theme_primary_color' => 'required|string|max:20',
            'theme_accent_color' => 'required|string|max:20',
            'theme_font_scale' => 'required|numeric|min:0.8|max:1.6',
            'theme_background_mode' => 'required|in:gradient,image',
            'theme_background_image_url' => 'nullable|string|max:255',
            'theme_logo_url' => 'nullable|string|max:255',
            'theme_background_image_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'theme_logo_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'business_name' => 'required|string|max:100',
            'theme_variant' => 'required|in:classic,light,dark,custom,vibrant',
        ]);

        // Aplicar presets según esquema de colores elegido.
        // En "Personalizado" se respetan los colores que el usuario escribió.
        switch ($data['theme_variant']) {
            case 'light':
                $primary = '#1f2937';
                $accent  = '#38bdf8';
                break;
            case 'dark':
                $primary = '#0b1220';
                $accent  = '#22c55e';
                break;
            case 'vibrant':
                // Esquema alegre tipo restaurante (rojo / dorado)
                $primary = '#b30000';
                $accent  = '#ffcc00';
                break;
            case 'classic':
                $primary = '#0f172a';
                $accent  = '#22c55e';
                break;
            case 'custom':
            default:
                $primary = $data['theme_primary_color'];
                $accent  = $data['theme_accent_color'];
                break;
        }

        // Si se sube un nuevo archivo de fondo, almacenarlo y usar su ruta como URL.
        if ($request->hasFile('theme_background_image_file')) {
            $path = $request->file('theme_background_image_file')->store('appearance', 'public');
            $data['theme_background_image_url'] = '/storage/' . $path;
        }

        // Si se sube un nuevo archivo de logo, almacenarlo y usar su ruta como URL.
        if ($request->hasFile('theme_logo_file')) {
            $path = $request->file('theme_logo_file')->store('appearance', 'public');
            $data['theme_logo_url'] = '/storage/' . $path;
        }

        Setting::set('theme_primary_color', $primary);
        Setting::set('theme_accent_color', $accent);
        Setting::set('theme_font_scale', (string) $data['theme_font_scale']);
        Setting::set('theme_background_mode', $data['theme_background_mode']);
        Setting::set('theme_background_image_url', $data['theme_background_image_url'] ?? '');
        Setting::set('theme_logo_url', $data['theme_logo_url'] ?? '');
        Setting::set('business_name', $data['business_name']);
        Setting::set('theme_variant', $data['theme_variant']);

        return redirect()->route('settings.appearance.edit');
    }

    public function editLocalization()
    {
        $this->authorizeAdmin();

        $locale = Setting::get('locale', config('app.locale', 'es'));
        $currencyDefault = Setting::get('currency_default', 'USD');
        $eurPerUsd = Setting::get('eur_per_usd', '');
        $clpPerUsd = Setting::get('clp_per_usd', '');
        $brlPerUsd = Setting::get('brl_per_usd', '');

        return view('settings.localization', compact('locale', 'currencyDefault', 'eurPerUsd', 'clpPerUsd', 'brlPerUsd'));
    }

    public function updateLocalization(Request $request)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'locale' => 'required|in:es,en,pt,de',
            'currency_default' => 'required|in:USD,EUR,CLP,VES,BRL',
            'eur_per_usd' => 'nullable|numeric|min:0',
            'clp_per_usd' => 'nullable|numeric|min:0',
            'brl_per_usd' => 'nullable|numeric|min:0',
        ]);

        Setting::set('locale', $data['locale']);
        Setting::set('currency_default', $data['currency_default']);
        Setting::set('eur_per_usd', $data['eur_per_usd'] ?? '');
        Setting::set('clp_per_usd', $data['clp_per_usd'] ?? '');
        Setting::set('brl_per_usd', $data['brl_per_usd'] ?? '');

        return redirect()->route('settings.localization.edit');
    }

    public function editFinances()
    {
        $this->authorizeAdmin();

        $financesEnabled = (bool) Setting::get('finances_enabled', 0);

        return view('settings.finances', compact('financesEnabled'));
    }

    public function updateFinances(Request $request)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'finances_enabled' => 'nullable|boolean',
        ]);

        $enabled = $request->boolean('finances_enabled');
        Setting::set('finances_enabled', $enabled ? 1 : 0);

        return redirect()->route('settings.finances.edit');
    }
}
