<?php

namespace App\Services;

use App\Models\Setting;

class CurrencyService
{
    public static function preferredCurrency(): string
    {
        $code = strtoupper((string) Setting::get('currency_default', 'USD'));
        $allowed = ['USD', 'EUR', 'CLP', 'VES', 'BRL'];

        return in_array($code, $allowed, true) ? $code : 'USD';
    }

    public static function symbol(string $code): string
    {
        return match (strtoupper($code)) {
            'EUR' => '€',
            'CLP' => 'CLP$',
            'VES' => 'Bs',
            'BRL' => 'R$',
            default => '$',
        };
    }

    public static function rateFromUsd(string $code, ?float $bcvAvg = null): ?float
    {
        $code = strtoupper($code);

        if ($code === 'USD') {
            return 1.0;
        }

        if ($code === 'VES') {
            $rate = $bcvAvg ?? (float) Setting::get('bcv_rate', 0);
            return $rate > 0 ? (float) $rate : null;
        }

        if ($code === 'EUR') {
            $rate = (float) Setting::get('eur_per_usd', 0);
            return $rate > 0 ? (float) $rate : null;
        }

        if ($code === 'CLP') {
            $rate = (float) Setting::get('clp_per_usd', 0);
            return $rate > 0 ? (float) $rate : null;
        }

        if ($code === 'BRL') {
            $rate = (float) Setting::get('brl_per_usd', 0);
            return $rate > 0 ? (float) $rate : null;
        }

        return null;
    }

    public static function convertFromUsd(float $amountUsd, string $targetCode, ?float $bcvAvg = null): ?float
    {
        $rate = self::rateFromUsd($targetCode, $bcvAvg);
        if (!$rate) {
            return null;
        }

        return $amountUsd * $rate;
    }
}
