<?php

namespace App\Http\Middleware;

use App\Exceptions\MaintenanceModeException;
use Closure;
use Illuminate\Http\Request;

class UnderMaintenance
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (config('config.system.enable_maintenance_mode') && \Auth::check() && ! \Auth::user()->is_default) {
            // \Auth::user()->logout();
            throw new MaintenanceModeException(__('general.errors.under_maintenance'));
        }

        return $next($request);
    }
}
