<?php

namespace App\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

trait HasEnum
{
    public static function translation(): string
    {
        return 'list';
    }

    public static function getKeys(): array
    {
        return Arr::pluck(self::cases(), 'value');
    }

    public static function getKeysWithAlias(): array
    {
        $keys = Arr::pluck(self::cases(), 'value');

        $aliasKeys = [];
        if (method_exists(static::class, 'aliases')) {
            foreach ($keys as $key) {
                $aliases = self::getAlias($key);
                $aliasKeys = array_merge($aliasKeys, $aliases);
            }
        }

        $keys = array_merge($keys, $aliasKeys);

        return $keys;
    }

    public static function isValid($key = ''): bool
    {
        $keys = self::getKeys();

        if ($key && in_array($key, $keys)) {
            return true;
        }

        return false;
    }

    public static function getOptions(array $except = []): array
    {
        $options = [];

        foreach (self::cases() as $option) {
            if (in_array($option->value, $except)) {
                continue;
            }

            $options[] = ['label' => trans(self::translation().$option->value), 'value' => $option->value];
        }

        return $options;
    }

    public static function getValue($value = null): ?self
    {
        if (! $value) {
            return null;
        }

        return self::tryFrom($value);
    }

    public static function getDetail(mixed $value = null): array
    {
        if (empty($value)) {
            return [];
        }

        if ($value instanceof Collection) {
            return $value->map(function ($item) {
                return self::detail($item);
            })->toArray();
        }

        return self::detail($value);
    }

    public static function detail(mixed $value)
    {
        if ($value instanceof self) {
            $value = $value->value;
        }

        $status = self::tryFrom($value);

        if (! $status) {
            return [];
        }

        $item = [
            'label' => trans(self::translation().$status->value),
            'value' => $status->value,
        ];

        if (method_exists(static::class, 'color')) {
            $item['color'] = $status->color();

            if (method_exists($status, 'colorValue')) {
                $item['hex_color'] = $status->colorValue();
            }
        }

        if (method_exists(static::class, 'getIcon')) {
            $item['icon'] = self::getIcon($status->value);
        }

        if (method_exists(static::class, 'translationDetail')) {
            $item['detail'] = trans(self::translationDetail().$status->value);
        }

        return $item;
    }

    public function label()
    {
        return trans(self::translation().$this->value);
    }

    public static function getLabel($value = null): ?string
    {
        if (! $value) {
            return '-';
        }

        $status = self::tryFrom($value);

        if (! $status) {
            return '-';
        }

        return trans(self::translation().$status->value);
    }

    public static function getLabels(string|array $values = []): ?string
    {
        if (is_string($values)) {
            $values = explode(',', $values);
        }

        foreach ($values as $value) {
            $labels[] = self::getLabel($value);
        }

        return implode(', ', $labels);
    }

    public static function getColor(mixed $value = null, bool $hexCode = false): ?string
    {
        if (! $value) {
            return '-';
        }

        $status = self::tryFrom($value);

        if (! $status) {
            return '-';
        }

        if ($hexCode) {
            return $status->colorValue();
        }

        return $status->color();
    }
}
