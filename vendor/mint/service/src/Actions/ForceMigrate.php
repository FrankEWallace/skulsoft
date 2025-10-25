<?php

namespace Mint\Service\Actions;

use App\Helpers\SysHelper;
use Illuminate\Validation\ValidationException;

class ForceMigrate
{
    public function execute()
    {
        if (SysHelper::isInstalled()) {
            throw ValidationException::withMessages(['message' => trans('setup.errors.could_not_migrate')]);
        }

        \Artisan::call('migrate', ['--force' => true]);
    }
}
