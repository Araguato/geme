<?php

namespace App\Http\Middleware;

use App\Services\LicenseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLicenseValid
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // Temporary: allow disabling license enforcement via env flag for pilot builds
        // Default is true so current installer runs without requiring a license.
        if (env('GEME_LICENSE_DISABLED', env('WAWI_LICENSE_DISABLED', true))) {
            return $next($request);
        }

        $path = '/'.ltrim($request->path(), '/');

        if (str_starts_with($path, '/license')) {
            return $next($request);
        }

        if (str_starts_with($path, '/build/') || str_starts_with($path, '/storage/') || str_starts_with($path, '/favicon') || str_starts_with($path, '/up')) {
            return $next($request);
        }

        $license = app(LicenseService::class)->status();

        if (($license['state'] ?? null) === 'db_unavailable') {
            return $next($request);
        }

        if (!($license['ok'] ?? false)) {
            return redirect()->route('license.show');
        }

        return $next($request);
    }
}
