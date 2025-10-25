<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

trait HasUuid
{
    public static $fake_uuid = null;

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    public static function bootHasUuid()
    {
        static::creating(function (Model $model) {
            $model->uuid = static::$fake_uuid ?? (string) Str::uuid();
        });
    }

    public function scopeFilterByUuid(Builder $query, ?string $uuid = null)
    {
        $query->when($uuid, function ($q, $uuid) {
            $q->where('uuid', '=', $uuid);
        });
    }

    public function scopeFindByUuidOrFail(Builder $query, ?string $uuid = null, $module = 'item', $field = 'message')
    {
        return $query
            ->where('uuid', '=', $uuid)
            ->firstOr(function () use ($module, $field) {
                throw ValidationException::withMessages([$field => trans('global.could_not_find', ['attribute' => $module])]);
            });
    }

    public function scopeGetOrFail(Builder $query, $module = 'item', $field = 'message')
    {
        return $query
            ->firstOr(function () use ($module, $field) {
                throw ValidationException::withMessages([$field => trans('global.could_not_find', ['attribute' => $module])]);
            });
    }

    public function scopeListOrFail(Builder $query, $module = 'item', $field = 'message')
    {
        $model = $query->get();

        if (! $model->count()) {
            throw ValidationException::withMessages([$field => trans('global.could_not_find', ['attribute' => $module])]);
        }

        return $model;
    }
}
