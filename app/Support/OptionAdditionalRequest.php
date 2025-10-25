<?php

namespace App\Support;

use Illuminate\Support\Arr;

class OptionAdditionalRequest
{
    public static $types = [
    ];

    public static function getDetail($type, $value = 'rules')
    {
        $default = [];
        if ($value == 'rules') {
            $default = ['details' => 'array'];
        }

        return Arr::get(self::$types, $type.'.'.$value, $default);
    }

    public static function getTransAttributes($type)
    {
        $attributes = self::getDetail($type, 'attributes');

        $newAttributes = [];

        foreach ($attributes as $key => $attribute) {
            $newAttributes[$key] = trans($attribute);
        }

        return $newAttributes;
    }
}
