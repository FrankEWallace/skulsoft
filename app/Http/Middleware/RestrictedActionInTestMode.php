<?php

namespace App\Http\Middleware;

use App\Helpers\SysHelper;
use Closure;
use Illuminate\Validation\ValidationException;

class RestrictedActionInTestMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (SysHelper::isTestMode()) {
            throw ValidationException::withMessages(['message' => __('general.errors.restricted_test_mode_action')]);
        }

        return $next($request);
    }
}
