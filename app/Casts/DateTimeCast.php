<?php

namespace App\Casts;

use App\ValueObjects\Cal;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class DateTimeCast implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        return Cal::datetime($value);
    }

    public function set($model, $key, $value, $attributes)
    {
        if ($value instanceof Cal) {
            if (empty($value->value)) {
                return null;
            }

            return $value->value;
        }

        if (empty($value)) {
            return null;
        }

        return $value;
    }
}
