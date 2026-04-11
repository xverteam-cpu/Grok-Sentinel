<?php

namespace App\Http\Controllers;

use App\Models\AccessGrant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AccessController extends Controller
{
    public const DEVICE_COOKIE = 'sentinel_device_id';
    public const TRUSTED_DEVICE_DAYS = 30;

    public static function trustedDeviceExpiry()
    {
        return now()->addDays(self::TRUSTED_DEVICE_DAYS);
    }

    public static function queueDeviceCookie(Request $request, string $deviceId): void
    {
        Cookie::queue(Cookie::make(
            self::DEVICE_COOKIE,
            $deviceId,
            60 * 24 * self::TRUSTED_DEVICE_DAYS,
            config('session.path', '/'),
            config('session.domain'),
            config('session.secure') ?? $request->isSecure(),
            config('session.http_only', true),
            config('session.partitioned', false),
            config('session.same_site', 'lax'),
        ));
    }

    public function show(Request $request): Response|RedirectResponse
    {
        if (self::hasPrivateAccess($request)) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/AccessGate', [
            'prefilledToken' => trim((string) $request->query('token', '')),
            'linkDetected' => $request->boolean('link'),
        ]);
    }

    public function showLink(string $token): RedirectResponse
    {
        return redirect()->route('access.show', [
            'token' => $token,
            'link' => 1,
        ])->with('success', 'Access link detected. Confirm this device to continue to login.');
    }

    public function redeem(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string'],
            'token' => ['nullable', 'string'],
        ]);

        $code = trim((string) ($validated['code'] ?? ''));
        $token = trim((string) ($validated['token'] ?? ''));

        if ($code === '' && $token === '') {
            return back()->withErrors([
                'code' => 'An access code or access link is required.',
            ]);
        }

        $query = AccessGrant::query()->active();

        if ($code !== '') {
            $query->where('code_hash', hash('sha256', Str::upper($code)));
        }

        if ($token !== '') {
            $query->where('link_token_hash', hash('sha256', $token));
        }

        $grant = $query->first();

        if (! $grant) {
            return back()->withErrors([
                'code' => 'This access credential is invalid or expired.',
            ]);
        }

        $deviceId = $request->cookie(self::DEVICE_COOKIE) ?: (string) Str::uuid();
        $deviceHash = hash('sha256', $deviceId);
        $userAgentHash = hash('sha256', (string) $request->userAgent());

        if ($grant->device_id_hash && $grant->device_id_hash !== $deviceHash) {
            return back()->withErrors([
                'code' => 'This access credential is already locked to another device.',
            ]);
        }

        if (! $grant->device_id_hash) {
            $grant->device_id_hash = $deviceHash;
            $grant->user_agent_hash = $userAgentHash;
            $grant->bound_at = now();
        } elseif ($grant->user_agent_hash !== $userAgentHash) {
            $grant->user_agent_hash = $userAgentHash;
        }

        $grant->last_used_at = now();
        $grant->expires_at = self::trustedDeviceExpiry();
        $grant->save();

        self::queueDeviceCookie($request, $deviceId);

        $request->session()->put('private_access_granted', true);

        return redirect()->route('login')->with('success', 'Device access granted.');
    }

    public static function hasValidDeviceAccess(Request $request): bool
    {
        $deviceId = $request->cookie(self::DEVICE_COOKIE);

        if (! $deviceId) {
            return false;
        }

        return AccessGrant::query()
            ->active()
            ->where('device_id_hash', hash('sha256', $deviceId))
            ->exists();
    }

    public static function hasPrivateAccess(Request $request): bool
    {
        if ($request->session()->get('private_access_granted')) {
            return true;
        }

        return self::hasValidDeviceAccess($request);
    }
}