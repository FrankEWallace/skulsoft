<?php

namespace App\Casts;

use App\ValueObjects\Currency;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class CurrencyCast implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        return Currency::from($value);
    }

    public function set($model, $key, $value, $attributes)
    {
        if ($value instanceof Currency) {
            return $value->name;
        }

        return $value;
    }
}
