<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use App\Services\GeoIpService;

class SetLocale
{
    private $countryToLocale = [
        'JP' => 'ja',
        'PH' => 'en',
        'US' => 'en',
        'GB' => 'en',
        'DE' => 'de',
        'FR' => 'fr',
        'ES' => 'es',
    ];

    public function handle(Request $request, Closure $next)
    {
        if (session()->has('locale')) {
            App::setLocale(session()->get('locale'));
        } else {
            $geoIp = new GeoIpService();
            $data = $geoIp->resolve($request->ip());

            if ($data && isset($this->countryToLocale[$data->country_code])) {
                $locale = $this->countryToLocale[$data->country_code];
                App::setLocale($locale);
                session()->put('locale', $locale);
            }
        }

        return $next($request);
    }
}