<?php

namespace Mint\Service\Services;

use App\Helpers\SysHelper;
use App\Support\ServerPreRequisite;

class InstallService
{
    use ServerPreRequisite;

    public function preRequisite()
    {
        $preRequisites = $this->getPreRequisite();

        $app = array(
            'title'    => config('app.name'),
            'version'  => SysHelper::getApp('VERSION'),
            'subtitle' => trans('setup.install.install_wizard'),
            'ip' => request()->ip(),
        );

        return compact('preRequisites', 'app');
    }
}
