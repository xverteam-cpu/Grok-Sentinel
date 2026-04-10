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

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'currentCountryCode' => $currentCountryCode,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'generatedAccess' => fn () => $request->session()->get('generatedAccess'),
            ],
        ];
    }
}
