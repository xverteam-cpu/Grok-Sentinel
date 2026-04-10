<?php

namespace App\Http\Middleware;

use App\Models\LoginSession;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $currentCountryCode = $request->user()
            ? LoginSession::query()
                ->where('user_id', $request->user()->id)
                ->latest('created_at')
                ->value('country_code')
            : null;

        $bankProfile = $request->user()?->bankProfile
            ? [
                'country_code' => $request->user()->bankProfile->country_code,
                'bank_name' => $request->user()->bankProfile->bank_name,
                'branch_code' => $request->user()->bankProfile->branch_code,
                'account_number' => $request->user()->bankProfile->account_number,
                'routing_number' => $request->user()->bankProfile->routing_number,
                'account_holder' => $request->user()->bankProfile->account_holder,
            ]
            : null;

        return [
            ...parent::share($request),
            'locale' => app()->getLocale(),
            'auth' => [
                'user' => $request->user(),
                'currentCountryCode' => $currentCountryCode,
                'bankProfile' => $bankProfile,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'generatedAccess' => fn () => $request->session()->get('generatedAccess'),
            ],
        ];
    }
}
