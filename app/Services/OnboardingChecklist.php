<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Models\Warehouse;

/**
 * Lista de "primeros pasos" con el estado real de la configuración.
 */
class OnboardingChecklist
{
    /** @return array<int, array{key:string,label:string,hint:string,done:bool,route:string}> */
    public static function steps(): array
    {
        $rate = (float) Setting::get('bcv_rate', 0);
        $taxId = trim((string) Setting::get('company_tax_id', ''));

        return [
            [
                'key' => 'company',
                'label' => 'Datos fiscales de la empresa',
                'hint' => 'Nombre y RIF: salen en facturas, libros y reportes.',
                'done' => trim((string) Setting::get('company_name', '')) !== '' && $taxId !== '',
                'route' => route('settings.company.edit'),
            ],
            [
                'key' => 'bcv',
                'label' => 'Tasa BCV del día',
                'hint' => 'El TPV la usa para calcular precios en bolívares.',
                'done' => $rate > 0,
                'route' => route('settings.bcv.edit'),
            ],
            [
                'key' => 'warehouse',
                'label' => 'Depósito',
                'hint' => 'Donde entra y sale la mercancía.',
                'done' => Warehouse::query()->exists(),
                'route' => route('warehouses.index'),
            ],
            [
                'key' => 'product',
                'label' => 'Primer producto',
                'hint' => 'Créalo a mano o impórtalo; sin productos no hay ventas.',
                'done' => Product::query()->exists(),
                'route' => route('products.index'),
            ],
            [
                'key' => 'fiscal',
                'label' => 'Medio de emisión fiscal',
                'hint' => 'Cómo se emiten las facturas (imprenta digital o prueba).',
                'done' => Setting::get('fiscal_provider', 'none') !== 'none',
                'route' => route('settings.fiscal.edit'),
            ],
            [
                'key' => 'cashier',
                'label' => 'Un usuario para vender',
                'hint' => 'Cajero o vendedor, para no usar la cuenta de administrador.',
                'done' => User::query()->whereHas('roles', fn ($q) => $q->whereIn('name', ['cajero', 'vendedor']))->exists(),
                'route' => route('users.index'),
            ],
        ];
    }

    public static function pending(): int
    {
        return count(array_filter(self::steps(), fn ($s) => ! $s['done']));
    }
}
