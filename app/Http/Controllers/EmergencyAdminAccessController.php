<?php

namespace App\Http\Controllers;

use App\Models\AccessGrant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class EmergencyAdminAccessController extends Controller
{
    public function __invoke(Request $request, User $user, string $nonce): RedirectResponse
    {
        abort_unless($request->hasValidSignature(), 403);
        abort_unless($user->is_admin, 403);

        $cacheKey = sprintf('sentinel:emergency-admin-link:%d:%s', $user->id, $nonce);

        abort_unless(Cache::pull($cacheKey), 403);

        $deviceId = $request->cookie(AccessController::DEVICE_COOKIE) ?: (string) Str::uuid();
        $deviceHash = hash('sha256', $deviceId);
        $userAgentHash = hash('sha256', (string) $request->userAgent());

        AccessGrant::query()->firstOrCreate(
            [
                'device_id_hash' => $deviceHash,
                'user_agent_hash' => $userAgentHash,
            ],
            [
                'bound_at' => now(),
                'last_used_at' => now(),
                'created_by' => $user->id,
            ],
        );

        Auth::login($user);
        $request->session()->regenerate();

        Cookie::queue(Cookie::make(AccessController::DEVICE_COOKIE, $deviceId, 60 * 24 * 365, '/', null, true, true, false, 'lax'));

        return redirect()->route('admin.dashboard')->with('success', 'Emergency admin access granted.');
    }
}