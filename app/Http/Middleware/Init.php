<?php

namespace App\Http\Middleware;

use App\Support\SetConfig;
use Closure;
use Illuminate\Http\Request;

class Init
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        (new SetConfig)->set();

        return $next($request);
    }
}
