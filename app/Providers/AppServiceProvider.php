<?php

namespace App\Providers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        $publicStoragePath = public_path('storage');
        $expectedTarget = storage_path('app/public');

        $expectedRealPath = realpath($expectedTarget) ?: $expectedTarget;

        $exists = File::exists($publicStoragePath);
        $isSymbolicLink = is_link($publicStoragePath);

        $needsLink = !$exists || !$isSymbolicLink;

        if (!$needsLink) {
            $currentTarget = realpath($publicStoragePath);
            $needsLink = $currentTarget === false || $currentTarget !== $expectedRealPath;
        }

        if ($needsLink) {
            try {
                if ($exists && !$isSymbolicLink) {
                    File::delete($publicStoragePath);
                }

                Artisan::call('storage:link');
                Log::info('Enlace storage recreado automáticamente.');
            } catch (\Throwable $exception) {
                Log::warning('No se pudo recrear el enlace storage automáticamente.', [
                    'message' => $exception->getMessage(),
                ]);
            }
        }
    }
}
