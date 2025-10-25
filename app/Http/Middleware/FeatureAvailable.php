<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Validation\ValidationException;

class FeatureAvailable
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next, $feature)
    {
        if (! config('config.'.$feature)) {
            throw ValidationException::withMessages(['message' => __('general.errors.feature_not_available')]);
        }

        return $next($request);
    }
}
