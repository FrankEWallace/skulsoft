<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class CountryFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'country';
    }
}
