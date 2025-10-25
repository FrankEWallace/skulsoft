<?php

use Illuminate\Support\Str;

expect()->extend('toBeUuid', function () {
    return Str::isUuid($this->value);
});

expect()->extend('toBeSlug', function () {
    $regex = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    return preg_match($regex, $this->value);
});

expect()->extend('toBeUrl', function () {
    $regex = '/^(http|https):\/\/[a-zA-Z0-9-\.]+\.[a-z]{2,4}/';

    return preg_match($regex, $this->value);
});
