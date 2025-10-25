<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SiteEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (! config('config.site.enable_site')) {
            return redirect()->route('app');
        }

        if (! auth()->check() && ! config('config.site.show_public_view')) {
            return redirect()->route('app');
        }

        config([
            'config.site.view' => 'site.'.config('config.site.theme').'.',
        ]);

        return $next($request);
    }
}
