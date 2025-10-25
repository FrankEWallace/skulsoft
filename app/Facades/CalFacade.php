<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class CalFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'cal';
    }
}
