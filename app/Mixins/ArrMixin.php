<?php

namespace App\Mixins;

use Illuminate\Support\Arr;

class ArrMixin
{
    /**
     * Get not empty values
     *
     * @param  array  $list
     */
    public function notEmpty()
    {
        return function ($list): array {
            return Arr::where($list, function ($item) {
                return ! empty($item) && ! is_null($item);
            });
        };
    }

    /**
     * Get not empty values
     *
     * @param  array  $list
     */
    public function implode()
    {
        return function ($list, $separator = ', '): string {
            return implode($separator, $list);
        };
    }

    /**
     * Get not empty values
     *
     * @param  array  $list
     */
    public function toAddress()
    {
        return function ($list = []): string|array {
            if (! is_array($list)) {
                $list = [];
            }

            $address = [
                'address_line1' => Arr::get($list, 'address_line1'),
                'address_line2' => Arr::get($list, 'address_line2'),
                'city' => Arr::get($list, 'city'),
                'state' => Arr::get($list, 'state'),
                'country' => Arr::get($list, 'country'),
                'zip_code' => Arr::get($list, 'zipcode'),
            ];

            $address = Arr::where($address, function ($item) {
                return ! empty($item) && ! is_null($item);
            });

            return implode(', ', $address);
        };
    }

    /**
     * Get json variables from resources in array
     *
     * @param  string  $list
     * @param  string  $type
     */
    public function getVar()
    {
        return function ($list, $type = null): array {
            $file = resource_path('var/'.($type ? ($type.'/') : '').$list.'.json');

            return (\File::exists($file)) ? \File::json($file) : [];
        };
    }

    /**
     * Get list by key
     *
     * @param  string  $key
     */
    public function getList()
    {
        return function ($key): array {
            $lists = Arr::getVar('list');

            return Arr::get($lists, $key, []);
        };
    }

    /**
     * Get translated list by key
     *
     * @param  string  $key
     * @param  bool  $sort
     */
    public function getTransList()
    {
        return function ($key, $sort = true): array {
            $list = Arr::getList($key);

            $data = [];
            foreach ($list as $item) {
                $data[] = ['value' => $item, 'label' => __('list.'.$key.'.'.$item)];
            }

            if ($sort) {
                array_multisort(array_map(function ($element) {
                    return $element['value'];
                }, $data), SORT_ASC, $data);
            }

            return $data;
        };
    }

    /**
     * Get select list
     *
     * @param  array  $items
     * @param  bool  $key
     */
    public function getSelectList()
    {
        return function (array $items, $key = 'value', $sort = true, string $trans = ''): array {
            $data = [];
            foreach ($items as $index => $item) {
                if (is_array($item)) {
                    $value = Arr::get($item, $key) ?? ($index + 1);
                    $item['value'] = $value;
                    $item['label'] = Arr::get($item, 'label') ?? $value;
                } else {
                    $item = ['value' => $item, 'label' => \Lang::has('list.'.$trans.'.'.$item) ? __('list.'.$trans.'.'.$item) : $item];
                }

                $data[] = $item;
            }

            if ($sort) {
                array_multisort(array_map(function ($element) {
                    return $element['label'];
                }, $data), SORT_ASC, $data);
            }

            return $data;
        };
    }

    /**
     * Search multidimension array by key & value
     *
     * @param  array  $data
     * @param  string  $key
     * @param  string  $value
     */
    public function searchByKey()
    {
        return function ($data, $key, $value): array {
            $index = array_search($value, array_column($data, $key));

            return ($index === false) ? [] : $data[$index];
        };
    }

    /**
     * Implode array to string
     *
     * @param  array  $data
     * @param  string  $key
     * @param  string  $value
     */
    public function toString()
    {
        return function ($data, $separator = ','): string {
            return implode($separator, $data);
        };
    }
}
