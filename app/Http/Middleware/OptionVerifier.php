<?php

namespace App\Http\Middleware;

use App\Enums\OptionType;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class OptionVerifier
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $type = $request->query('type') ?? $request->input('type');

        $option = OptionType::tryFrom($type);

        if (! $option) {
            return response()->json(['message' => __('general.errors.invalid_action')], 422);
        }

        $detail = $option->detail();

        $permission = Arr::get($detail, 'permission');

        if ($request->route()->getName() != 'options.index' && ! \Auth::user()->can($permission)) {
            return response()->json(['message' => __('user.errors.permission_denied')], 422);
        }

        $module = Arr::get($detail, 'module');
        $subModule = Arr::get($detail, 'sub_module');
        $trans = trans($module.'.'.$subModule.'.'.$subModule);

        $request->merge([
            'type' => $type,
            'trans' => $trans,
            'team' => Arr::get($detail, 'team', false),
        ]);

        return $next($request);
    }
}
