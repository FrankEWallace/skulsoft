<?php

namespace App\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait SimpleValidation
{
    public function simplify($validator, $key, $new_key)
    {
        if ($validator->errors()->has($key)) {
            $error_key = Arr::first(array_filter($validator->errors()->keys(), function ($item) use ($key) {
                return Str::is($key, $item);
            }));

            if (preg_match('/[0-9]/', $error_key, $match)) {
                $validator->errors()->add(preg_replace('/\*/', array_shift($match), $new_key), $validator->errors()->first($key));
            }
        }
    }

    public function change($validator, $key, $new_key)
    {
        if ($validator->errors()->has($key)) {
            $validator->errors()->add($new_key, $validator->errors()->first($key));
        }
    }
}
