<?php

namespace App\Services;

use App\Models\ExchangeRate;
use App\Models\Setting;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class ExchangeRateService
{
    public static function getRateForDate($date): float
    {
        $day = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();

        $rate = ExchangeRate::query()
            ->where('rate_date', '<=', $day)
            ->orderByDesc('rate_date')
            ->value('bs_per_usd');

        if (!is_null($rate)) {
            return (float) $rate;
        }

        return (float) Setting::get('bcv_rate', 0);
    }

    public static function getDailyRates(string $dateFrom, string $dateTo): array
    {
        $from = Carbon::parse($dateFrom)->startOfDay();
        $to = Carbon::parse($dateTo)->startOfDay();

        $rates = ExchangeRate::query()
            ->where('rate_date', '<=', $to->toDateString())
            ->orderBy('rate_date')
            ->get(['rate_date', 'bs_per_usd']);

        $lastKnown = null;
        $ratesByDate = [];
        foreach ($rates as $row) {
            $ratesByDate[$row->rate_date->toDateString()] = (float) $row->bs_per_usd;
        }

        $period = CarbonPeriod::create($from, $to);
        $out = [];
        foreach ($period as $day) {
            $key = $day->toDateString();
            if (array_key_exists($key, $ratesByDate)) {
                $lastKnown = $ratesByDate[$key];
            }
            if (is_null($lastKnown)) {
                $lastKnown = (float) Setting::get('bcv_rate', 0);
            }
            $out[$key] = (float) $lastKnown;
        }

        return $out;
    }
}
