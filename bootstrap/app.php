<?php

use App\Http\Middleware\SetLocaleFromSettings;
use App\Http\Middleware\EnsureLicenseValid;
use App\Http\Middleware\TrackUserActivity;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\NoStoreAuthenticatedResponses;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            EnsureLicenseValid::class,
            SetLocaleFromSettings::class,
            TrackUserActivity::class,
            NoStoreAuthenticatedResponses::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Errores inesperados: página amable con un código que soporte puede buscar en el log.
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if (config('app.debug') || $request->expectsJson()) {
                return null;
            }
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                || $e instanceof \Illuminate\Validation\ValidationException
                || $e instanceof \Illuminate\Auth\AuthenticationException
                || $e instanceof \Illuminate\Auth\Access\AuthorizationException
                || $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
                || $e instanceof \Illuminate\Session\TokenMismatchException) {
                return null;
            }

            $code = 'ERR-'.now()->format('ymd').'-'.strtoupper(\Illuminate\Support\Str::random(5));
            \Illuminate\Support\Facades\Log::error($code.' '.$e->getMessage(), [
                'url' => $request->fullUrl(),
                'user_id' => $request->user()?->id,
                'exception' => $e,
            ]);

            return response()->view('errors.500', ['errorCode' => $code], 500);
        });
    })->create();
