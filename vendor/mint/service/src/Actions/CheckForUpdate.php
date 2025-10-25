<?php

namespace Mint\Service\Actions;

use App\Helpers\SysHelper;
use Mint\Service\Events\ProductUpdate;

class CheckForUpdate
{
    public function execute(): void
    {
        if (SysHelper::getApp('UPDATE') == today()->toDateString()) {
            return;
        }

        if (SysHelper::isTestMode()) {
            return;
        }

        if (!SysHelper::isConnected()) {
            return;
        }

        $data = (new GetData)->execute();

        event(new ProductUpdate($data));

        SysHelper::setApp(['UPDATE' => today()->toDateString()]);
    }
}
