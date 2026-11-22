<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Las respuestas para usuarios con sesión (informes, fichas de clientes, nómina,
 * exportaciones…) no deben guardarse en la caché del navegador ni de intermediarios:
 * en equipos compartidos quedarían accesibles tras cerrar sesión (botón "atrás").
 */
class NoStoreAuthenticatedResponses
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->user()) {
            $response->headers->set('Cache-Control', 'no-store, private, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }
}
