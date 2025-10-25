<?php

namespace App\Mixins;

use Illuminate\Validation\ValidationException;

class CollectionMixin
{
    public function getOrFail()
    {
        return function ($module = 'item', $field = 'message') {
            if (! is_null($item = $this->first())) {
                return $item;
            }

            throw ValidationException::withMessages([$field => trans('global.could_not_find', ['attribute' => $module])]);
        };
    }

    public function hasOrFail()
    {
        return function ($message = 'item', $field = 'message') {
            if (! is_null($item = $this->first())) {
                return $item;
            }

            throw ValidationException::withMessages([$field => $message]);
        };
    }
}
