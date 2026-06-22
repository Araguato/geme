<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;

class SetLocaleFromSettings
{
    public function handle(Request $request, Closure $next)
    {
        $locale = (string) Setting::get('locale', config('app.locale'));

        $allowed = ['es', 'en', 'pt', 'de'];
        if (!in_array($locale, $allowed, true)) {
            $locale = 'es';
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
