<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeoIpService
{
    public function resolve(string $ip): ?object
    {
        if (in_array($ip, ['127.0.0.1', '::1'], true)) {
            return null;
        }

        $provider = config('services.geoip.provider', 'ipapi');

        if ($provider === 'ipapi') {
            $response = Http::acceptJson()
                ->timeout(4)
                ->get("https://ipapi.co/{$ip}/json/");

            if (! $response->ok() || $response->json('error')) {
                return null;
            }

            return (object) [
                'country_code' => strtoupper((string) $response->json('country')),
                'region' => $response->json('region'),
                'city' => $response->json('city'),
                'latitude' => $response->json('latitude'),
                'longitude' => $response->json('longitude'),
                'timezone' => $response->json('timezone'),
            ];
        }

        return null;
    }
}
