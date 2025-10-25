<?php

namespace App\Casts;

use App\ValueObjects\Price;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class PriceCast implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        return Price::from($value);
    }

    public function set($model, $key, $value, $attributes)
    {
        if ($value instanceof Price) {
            return $value->value;
        }

        return $value;
    }
}
