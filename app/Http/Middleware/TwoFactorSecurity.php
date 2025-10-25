<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;

class TwoFactorSecurity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (config('config.auth.enable_two_factor_security') && session()->exists('two_factor_security')) {
            throw new AuthenticationException(__('auth.security.two_factor_security_pending'));
        }

        return $next($request);
    }
}
