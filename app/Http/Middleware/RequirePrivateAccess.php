<?php

namespace App\Http\Middleware;

use App\Http\Controllers\AccessController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePrivateAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            return $next($request);
        }

        if ($request->routeIs('access.*')) {
            return $next($request);
        }

        if (AccessController::hasValidDeviceAccess($request)) {
            return $next($request);
        }

        return redirect()->route('access.show');
    }
}