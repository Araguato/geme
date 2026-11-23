<?php

namespace App\Services\Fiscal;

use App\Models\Setting;

/**
 * Elige el medio de emisión de la tienda. Por defecto NINGUNO: GEME se comporta como
 * hasta ahora (registra ventas y libros, sin emitir por un medio externo).
 * Para añadir una imprenta real: crear su driver (FiscalProvider) y registrarlo aquí.
 */
class FiscalManager
{
    /** @var array<string,class-string<FiscalProvider>> */
    public const PROVIDERS = [
        'sandbox' => SandboxProvider::class,
    ];

    public const LABELS = [
        'none' => 'Ninguno (como hasta ahora)',
        'sandbox' => 'Proveedor de pruebas (simulado, sin validez fiscal)',
    ];

    public static function key(): string
    {
        $key = Setting::get('fiscal_provider', 'none');

        return isset(self::PROVIDERS[$key]) ? $key : 'none';
    }

    public static function enabled(): bool
    {
        return self::key() !== 'none';
    }

    public static function provider(?string $key = null): ?FiscalProvider
    {
        $key ??= self::key();
        $class = self::PROVIDERS[$key] ?? null;

        return $class ? app($class) : null;
    }

    /** Los proveedores de prueba nunca tienen validez fiscal. */
    public static function environment(): string
    {
        return self::key() === 'sandbox' ? 'test' : (Setting::get('fiscal_environment') === 'production' ? 'production' : 'test');
    }
}
