<?php

namespace App\QueryFilters;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class WhereInMatch
{
    public function handle($request, Closure $next, ...$options)
    {
        $column = Arr::first($options);
        array_shift($options);

        $search = request()->query(Arr::first($options));

        if (! $search) {
            return $next($request);
        }

        $items = Str::toArray($search);

        $builder = $next($request);

        return $builder->when($items, function ($q, $items) use ($column) {
            $q->whereIn($column, $items);
        });
    }
}
