<?php

namespace App\Actions\Config;

use App\Support\BuildConfig;
use Closure;

class SetAppConfig
{
    use BuildConfig;

    public function handle($config, Closure $next)
    {
        $config = $this->generate($config);

        return $next($config);
    }
}
