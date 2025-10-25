<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;

class ScreenLock
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (config('config.auth.enable_screen_lock') && session()->exists('screen_lock')) {
            throw new AuthenticationException(__('auth.screen_lock.screen_lock_pending'));
        }

        return $next($request);
    }
}
