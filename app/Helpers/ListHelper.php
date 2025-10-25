<?php

namespace App\Helpers;

use Illuminate\Support\Arr;

class ListHelper
{
    /**
     * Get list key
     */
    public static function getListKey(string $list): array
    {
        $lists = Arr::getVar('list');

        $items = [];
        if (Arr::has($lists, $list)) {
            $items = Arr::get($lists, $list, []);
        }

        return $items;
    }

    /**
     * Get lists from json
     */
    public static function getLists(array $types = []): array
    {
        $lists = Arr::getVar('list');

        $data = [];
        foreach ($types as $type) {
            if (Arr::has($lists, $type)) {
                $list = Arr::get($lists, $type, []);
                $data[$type] = request()->query('simple') ? $list : Arr::getSelectList(items: $list, trans: $type);
            }
        }

        return $data;
    }

    /**
     * Get list
     */
    public static function getList(string $list = 'list', string $key = 'value'): array
    {
        $items = Arr::getVar($list);

        return Arr::getSelectList($items, $key);
    }

    /**
     * Get list by key
     */
    public static function getListByKey(string $list, string $key, ?string $value = null): array
    {
        if (is_null($value)) {
            return [];
        }

        return Arr::first(self::getList($list, $key), function ($item) use ($value, $key) {
            return Arr::get($item, $key) === $value;
        }, []);
    }

    /**
     * Get list by id
     */
    public static function getListById(string $name, ?string $id = null): array
    {
        return Arr::first(Arr::getTransList($name), function ($value, $key) use ($id) {
            return Arr::get($value, 'value') === $id;
        }, []);
    }

    /**
     * Get list value
     */
    public static function getListValue(string $list, string $key, string $value, string $data): mixed
    {
        $item = Arr::first(Arr::getVar($list), function ($item) use ($value, $key) {
            return Arr::get($item, $key) === $value;
        }, []);

        return Arr::get($item, $data);
    }
}
