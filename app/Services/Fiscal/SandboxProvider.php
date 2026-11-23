<?php

namespace App\Services\Fiscal;

use App\Models\FiscalDocument;
use App\Models\Setting;

/**
 * Proveedor de PRUEBAS: simula una imprenta digital con numeración consecutiva.
 * Los números llevan el prefijo "PRUEBA-" para que nunca se confundan con una factura real.
 */
class SandboxProvider implements FiscalProvider
{
    public function key(): string
    {
        return 'sandbox';
    }

    public function issue(array $payload): FiscalResult
    {
        if (Setting::get('fiscal_sandbox_fail') === '1') {
            return FiscalResult::failure('Fallo simulado del proveedor de pruebas.', true);
        }

        $next = (int) FiscalDocument::where('provider', 'sandbox')->where('status', 'issued')->count() + 1;
        $number = str_pad((string) $next, 8, '0', STR_PAD_LEFT);

        return FiscalResult::success('PRUEBA-' . $number, '00-' . $number, 'sbx-' . substr(sha1(json_encode($payload)), 0, 12));
    }

    public function testConnection(): FiscalResult
    {
        return FiscalResult::success('-', '-');
    }
}
