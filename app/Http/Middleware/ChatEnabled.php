<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ChatEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (! config('config.chat.enable_chat')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('general.errors.feature_not_available')], 403);
            } else {
                return redirect()->route('app');
            }
        }

        return $next($request);
    }
}
