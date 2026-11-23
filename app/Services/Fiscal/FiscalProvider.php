<?php

namespace App\Services\Fiscal;

/**
 * Medio autorizado de emisión (imprenta digital, proveedor tecnológico…).
 * Cada imprenta real será un driver que implemente esta interfaz; el TPV no cambia.
 */
interface FiscalProvider
{
    /** Identificador estable guardado en settings (p. ej. "sandbox"). */
    public function key(): string;

    /** Emite el documento descrito por el payload y devuelve número de factura y de control. */
    public function issue(array $payload): FiscalResult;

    /** Comprueba credenciales y conectividad sin emitir nada. */
    public function testConnection(): FiscalResult;
}
