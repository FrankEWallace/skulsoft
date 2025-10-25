<?php

namespace App\QueryFilters;

use Closure;
use Illuminate\Support\Arr;

class ExactMatch
{
    public function handle($request, Closure $next, ...$options)
    {
        $search = request()->query(Arr::first($options));

        if (count($options) > 1) {
            array_shift($options);
        }

        $builder = $next($request);

        return $builder->when($search, function ($q, $search) use ($options) {
            $q->where(function ($q) use ($search, $options) {
                foreach ($options as $field) {
                    $q->orWhere($field, '=', $search);
                }
            });
        });
    }
}
