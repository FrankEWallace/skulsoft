<?php

namespace App\Actions\Config;

use Closure;

class GetAssetConfig
{
    public function handle($config, Closure $next)
    {
        $config['assets'] = config('config.assets');

        return $next($config);
    }
}
