<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class PriceFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'price';
    }
}
