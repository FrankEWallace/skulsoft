<?php

namespace App\Concerns;

use Illuminate\Support\Arr;

trait ListItem
{
    public static function getOptions(): array
    {
        $options = [];

        foreach (self::$items as $option) {
            $options[] = ['label' => trans(self::$trans.$option), 'value' => $option];
        }

        return $options;
    }

    public static function getValues($type = 'array'): mixed
    {
        $values = Arr::pluck(self::getOptions(), 'value');

        if ($type === 'array') {
            return $values;
        }

        return implode(',', $values);
    }

    public static function getDetail($value = ''): ?array
    {
        return Arr::first(self::$details, function ($item) use ($value) {
            return Arr::get($item, 'status') == $value;
        });
    }
}
