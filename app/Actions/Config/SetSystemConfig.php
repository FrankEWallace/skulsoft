<?php

namespace App\Actions\Config;

use Closure;
use Illuminate\Support\Arr;

class SetSystemConfig
{
    public function handle($config, Closure $next)
    {
        config(['config' => $config]);

        config([
            // 'session.lifetime' => config('config.auth.session_lifetime', 1440),
            'config.system.currency_detail' => collect(Arr::getVar('currencies'))->firstWhere('name', Arr::get($config, 'system.currency')),
            'config.layout.display' => \Auth::check() ? \Auth::user()->user_display : (config('config.system.enable_dark_theme') ? 'dark' : 'light'),
            'config.system.upload_prefix' => '',
            'config.print.custom_path' => 'print.custom.',
        ]);

        config([
            'app.name' => config('config.general.app_name'),
            'app.locale' => config('config.system.locale'),
        ]);

        return $next($config);
    }
}
