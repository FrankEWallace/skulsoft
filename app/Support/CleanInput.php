<?php

namespace App\Support;

use Illuminate\Support\Arr;

trait CleanInput
{
    public function clean($input, array $except = [])
    {
        $selectedInput = Arr::except($input, $except);

        array_walk_recursive($selectedInput, function (&$value) {
            $value = strip_tags($value);
        });

        return array_merge($selectedInput, Arr::only($input, $except));
    }
}
