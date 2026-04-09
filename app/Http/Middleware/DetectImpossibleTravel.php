<?php

namespace App\Http\Middleware;

use App\Models\IncidentLog;
use App\Models\LoginSession;
use App\Services\GeoIpService;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DetectImpossibleTravel
{
    public function __construct(protected GeoIpService $geoIp)
    {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        $ip = $request->ip();
        $location = $this->geoIp->resolve($ip);

        if ($location && ! empty($location->country_code)) {
            $previousSession = LoginSession::where('user_id', $user->id)
                ->where('created_at', '>=', Carbon::now()->subHour())
                ->orderBy('created_at', 'desc')
                ->first();

            if ($previousSession && $previousSession->country_code !== $location->country_code) {
                $this->logIncident($user->id, $request, $location, $previousSession);
            }

            if (! $previousSession || $previousSession->ip_address !== $ip) {
                LoginSession::create([
                    'user_id' => $user->id,
                    'ip_address' => $ip,
                    'country_code' => $location->country_code,
                    'region' => $location->region,
                    'city' => $location->city,
                    'user_agent' => $request->userAgent(),
                    'metadata' => json_encode([
                        'latitude' => $location->latitude,
                        'longitude' => $location->longitude,
                        'timezone' => $location->timezone,
                    ]),
                ]);
            }
        }

        return $next($request);
    }

    protected function logIncident(int $userId, Request $request, object $currentLocation, LoginSession $previousSession): void
    {
        $metadata = [
            'current' => [
                'ip' => $request->ip(),
                'country' => $currentLocation->country_code,
                'region' => $currentLocation->region,
                'city' => $currentLocation->city,
                'user_agent' => $request->userAgent(),
            ],
            'previous' => [
                'ip' => $previousSession->ip_address,
                'country' => $previousSession->country_code,
                'region' => $previousSession->region,
                'city' => $previousSession->city,
                'timestamp' => $previousSession->created_at->toISOString(),
            ],
            'time_diff_minutes' => Carbon::now()->diffInMinutes($previousSession->created_at),
        ];

        IncidentLog::create([
            'user_id' => $userId,
            'event_type' => 'impossible_travel',
            'source_ip' => $request->ip(),
            'country_code' => $currentLocation->country_code,
            'city' => $currentLocation->city,
            'region' => $currentLocation->region,
            'user_agent' => $request->userAgent(),
            'risk_score' => 85,
            'metadata' => $metadata,
            'detected_at' => now(),
        ]);
    }
}
