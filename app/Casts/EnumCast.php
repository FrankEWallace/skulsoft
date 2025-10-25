<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class EnumCast implements CastsAttributes
{
    protected $enumClass;

    public function __construct($enumClass)
    {
        $this->enumClass = $enumClass;
    }

    public function get($model, $key, $value, $attributes)
    {
        if (empty($value)) {
            return null;
        }

        return $this->enumClass::tryFrom($value);
    }

    public function set($model, $key, $value, $attributes)
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof $this->enumClass) {
            return $value->value;
        }

        return (string) $value;
    }
}
