<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pipeline\Pipeline;

trait HasFilter
{
    public static function bootHasFilter() {}

    public function scopeFilter(Builder $query, array $filters = [])
    {
        return app(Pipeline::class)
            ->send($query)
            ->through($filters)
            ->thenReturn();
    }
}
