<?php

namespace Mint\Service\Actions;

use App\Models\Config\Config;
use Closure;

class SeedConfig
{
    public function handle($params, Closure $next)
    {
        \Artisan::call('db:seed', ['--class' => 'TemplateSeeder', '--force' => true]);

        $config = Config::firstOrCreate(['name' => 'system']);
        $config->value = [
            'show_setup_wizard' => true,
        ];
        $config->save();

        return $next($params);
    }
}
