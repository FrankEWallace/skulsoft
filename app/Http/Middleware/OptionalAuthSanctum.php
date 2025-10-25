<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OptionalAuthSanctum
{
    public function handle(Request $request, Closure $next, ...$guards)
    {
        if ($request->header('device-type') == 'mobile' && $request->bearerToken()) {
            try {
                Auth::shouldUse('sanctum');
                Auth::authenticate($guards);
            } catch (\Exception $e) {
                // Silently continue if authentication fails
            }
        }

        return $next($request);
    }
}
