<?php

namespace Mint\Service\Actions;

use Closure;
use App\Helpers\SysHelper;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SetAppInstallation
{
    public function handle($params, Closure $next)
    {
        SysHelper::setApp([
            'INSTALLED' => Arr::get($params, 'checksum'),
            'AC'        => Arr::get($params, 'access_code'),
            'EMAIL'     => Arr::get($params, 'registered_email'),
        ]);

        \Storage::disk('local')->delete('.reinstall');

        return $next($params);
    }
}
