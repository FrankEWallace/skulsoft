<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ExportItem
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $request->merge(['export' => true]);

        return $next($request);
    }
}
