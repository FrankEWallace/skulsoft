<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class SiteFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'site';
    }
}
