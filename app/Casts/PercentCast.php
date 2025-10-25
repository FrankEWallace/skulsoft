<?php

namespace App\Casts;

use App\ValueObjects\Percent;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class PercentCast implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        return Percent::from($value);
    }

    public function set($model, $key, $value, $attributes)
    {
        return $value;
    }
}
