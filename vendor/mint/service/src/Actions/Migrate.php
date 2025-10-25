<?php

namespace Mint\Service\Actions;

use Closure;
use Illuminate\Support\Arr;

class Migrate
{
    public function handle($params, Closure $next)
    {
        if (!Arr::get($params, 'db_imported')) {
            \Artisan::call('migrate', ['--force' => true]);
        }

        \Artisan::call('key:generate', ['--force' => true]);

        return $next($params);
    }
}
