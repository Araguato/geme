<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GeocodingService
{
    public static function geocodeToVenezuela(string $address, ?string $locationHint = null): ?array
    {
        $q = trim($address);
        if ($locationHint) {
            $q = trim($q . ', ' . $locationHint);
        }

        $q = trim($q . ', La Victoria, Aragua, Venezuela');

        $cacheKey = 'geocode:nominatim:' . sha1(mb_strtolower($q));

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($q) {
            try {
                $response = Http::timeout(10)
                    ->acceptJson()
                    ->withHeaders([
                        'User-Agent' => (string) config('app.name', 'WAWI') . ' Geocoder',
                    ])
                    ->get('https://nominatim.openstreetmap.org/search', [
                        'q' => $q,
                        'format' => 'jsonv2',
                        'limit' => 1,
                        'addressdetails' => 0,
                        'countrycodes' => 've',
                        'viewbox' => '-67.43,10.33,-67.24,10.16',
                        'bounded' => 0,
                    ]);
            } catch (\Throwable $e) {
                return null;
            }

            if (!$response->ok()) {
                return null;
            }

            $data = $response->json();
            if (!is_array($data) || empty($data[0]['lat']) || empty($data[0]['lon'])) {
                return null;
            }

            return [
                'latitude' => (float) $data[0]['lat'],
                'longitude' => (float) $data[0]['lon'],
                'display_name' => $data[0]['display_name'] ?? null,
            ];
        });
    }
}
