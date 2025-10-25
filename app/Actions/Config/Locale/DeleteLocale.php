<?php

namespace App\Actions\Config\Locale;

use App\Concerns\LocalStorage;
use Illuminate\Support\Arr;

class DeleteLocale
{
    use LocalStorage;

    protected $storage_key = 'locales';

    public function execute(string $code)
    {
        $collection = collect($this->getKey($this->storage_key));

        $filtered = $collection->filter(function ($item, $key) use ($code) {
            return Arr::get($item, 'code') != $code;
        });

        $this->setStorage($this->storage_key, $filtered->all());

        \File::deleteDirectory(base_path('lang/'.$code));
    }
}
