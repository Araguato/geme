<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Usage in routes: ->middleware('role:admin|cajero')
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Unauthorized');
        }

        $roleArray = array_filter(explode('|', $roles));

        // Nutzt die bestehenden Helper-Methoden im User-Model
        if (method_exists($user, 'hasAnyRole')) {
            if (! $user->hasAnyRole($roleArray)) {
                abort(403, 'This action is unauthorized.');
            }
        } elseif (method_exists($user, 'hasRole')) {
            // Fallback: nur ein einzelner Role-Parameter
            $allowed = false;
            foreach ($roleArray as $role) {
                if ($user->hasRole($role)) {
                    $allowed = true;
                    break;
                }
            }

            if (! $allowed) {
                abort(403, 'This action is unauthorized.');
            }
        } else {
            // Wenn das User-Model keine Role-Methoden kennt, lieber sofort 500 werfen
            abort(500, 'Role checking not configured on User model.');
        }

        return $next($request);
    }
}
