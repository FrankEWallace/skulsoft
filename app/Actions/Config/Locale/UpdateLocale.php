<?php

namespace App\Actions\Config\Locale;

use App\Concerns\LocalStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class UpdateLocale
{
    use LocalStorage;

    protected $storage_key = 'locales';

    public function execute(Request $request, string $code)
    {
        $collection = collect($this->getKey($this->storage_key));

        $collection->transform(function ($item, $key) use ($request, $code) {
            if (Arr::get($item, 'code') === $code) {
                return [
                    'name' => $request->name,
                    'code' => $code,
                    'uuid' => $code,
                ];
            } else {
                return $item;
            }
        });

        $this->setStorage($this->storage_key, $collection->all());
    }
}
