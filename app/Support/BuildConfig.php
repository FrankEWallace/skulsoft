<?php

namespace App\Support;

use Illuminate\Support\Arr;

trait BuildConfig
{
    public function generate(array $config, array $params = []): array
    {
        $mask = (bool) Arr::get($params, 'mask', false);
        $showPublic = (bool) Arr::get($params, 'show_public', false);
        $hideHtml = (bool) Arr::get($params, 'hide_html', false);

        $systemConfigs = Arr::getVar('config');

        $configs = [];
        foreach ($systemConfigs as $index => $systemConfig) {
            $dbConfig = Arr::get($config, $index, []) ?? [];

            foreach ($dbConfig as $key => $value) {
                if (! collect($systemConfig)->where('name', $key)->first()) {
                    $systemConfig[] = ['name' => $key, 'value' => $value, 'is_public' => true];
                }
            }

            $configs[$index] = $systemConfig;
        }

        return collect($configs)->transform(function ($systemConfig, $key) use ($config, $showPublic, $hideHtml, $mask) {
            return collect($systemConfig)->transform(function ($item, $index) use ($config, $key, $mask) {
                $value = Arr::get($config, $key.'.'.Arr::get($item, 'name'));
                $item['value'] = $value ?? Arr::get($item, 'value');

                if (Arr::get($item, 'is_secret', false) && $mask) {
                    $item['value'] = Arr::get($item, 'value') ? config('app.mask') : '';
                }

                return $item;
            })->when($showPublic, function ($collection) {
                return $collection->where('is_public', true);
            })->when($hideHtml, function ($collection) {
                return $collection->where('is_html', false);
            })->pluck('value', 'name')->all();
        })->all();
    }
}
