<?php

namespace App\Actions\Config;

use App\Helpers\SysHelper;
use App\Models\Config\Config;
use Illuminate\Pipeline\Pipeline;

class GetConfig
{
    public function execute()
    {
        if (! SysHelper::isInstalled()) {
            return ['requiresInstall' => true];
        }

        return app(Pipeline::class)
            ->send(Config::listAll())
            ->through([
                \App\Actions\Config\GetAppConfig::class,
                \App\Actions\Config\GetAssetConfig::class,
                \App\Actions\Config\GetGeneralConfig::class,
            ])
            ->thenReturn();
    }
}
