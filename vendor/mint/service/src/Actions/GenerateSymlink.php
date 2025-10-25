<?php

namespace Mint\Service\Actions;

use Closure;
use Illuminate\Support\Arr;

class GenerateSymlink
{
    public function handle($params, Closure $next)
    {
        if (Arr::get($params, 'skip_symlink') === 'yes') {
            return $next($params);
        }

        if (\File::exists(public_path('storage'))) {
            \File::deleteDirectory(public_path('storage'));
        }

        if (!\File::exists(storage_path('app/public'))) {
            \Storage::makeDirectory('public');
        }

        try {
            \Artisan::call('storage:link');
        } catch (\Exception $e) {
            // do nothing
        }

        return $next($params);
    }
}
