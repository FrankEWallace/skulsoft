<?php

namespace Mint\Service\Actions;

use Illuminate\Pipeline\Pipeline;

class Install
{
    public function execute()
    {
        $results = app(Pipeline::class)
            ->send(request()->all())
            ->through([
                ValidateLicense::class,
                SetENV::class,
                Migrate::class,
                SeedTeam::class,
                SeedRoleAndPermission::class,
                SeedConfig::class,
                SeedUser::class,
                Setup::class,
                GenerateSymlink::class,
                SetAppInstallation::class
            ])
            ->thenReturn();
    }
}
