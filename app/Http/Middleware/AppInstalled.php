<?php

namespace App\Http\Middleware;

use App\Helpers\SysHelper;
use Closure;

class AppInstalled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (! SysHelper::isInstalled() && ! $this->toIgnore($request)) {
            if ($request->ajax()) {
                return response()->json(['failedInstall' => true]);
            } else {
                abort(404);
            }
        }

        return $next($request);
    }

    private function toIgnore($request)
    {
        $except = [
            'install.store',
            'install.validate',
            'install.preRequisite',
            'app',
        ];

        foreach ($except as $name) {
            if ($request->url() == route($name)) {
                return true;
            }
        }

        return false;
    }
}
