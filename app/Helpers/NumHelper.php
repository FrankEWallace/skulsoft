<?php

namespace App\Helpers;

class NumHelper
{
    public static function percentChanged(float $firstVal = 0, float $secondVal = 0): float
    {
        return $secondVal ? round(($firstVal - $secondVal) / $secondVal * 100) : 100;
    }
}
