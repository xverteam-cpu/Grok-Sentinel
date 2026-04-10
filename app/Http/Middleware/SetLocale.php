<?php

namespace App\Http\Middleware;

use App\Models\LoginSession;
use Closure;
use Illuminate\Support\Facades\App;
use App\Services\GeoIpService;
use Illuminate\Http\Request;

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
        if ($request->user()) {
            $countryCode = LoginSession::query()
                ->where('user_id', $request->user()->id)
                ->latest('created_at')
                ->value('country_code');

            if ($countryCode && isset($this->countryToLocale[$countryCode])) {
                $locale = $this->countryToLocale[$countryCode];
                App::setLocale($locale);
                session()->put('locale', $locale);

                return $next($request);
            }
        }

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