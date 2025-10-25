<?php

namespace App\QueryFilters;

use App\Helpers\CalHelper;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Arr;

class DateBetween
{
    public function handle($request, Closure $next, ...$options)
    {
        if (count($options) < 3) {
            return $next($request);
        }

        $startDate = request()->query(Arr::first($options));
        array_shift($options);

        $endDate = request()->query(Arr::first($options));
        array_shift($options);

        $field = Arr::first($options);

        $secondField = null;
        if (count($options) > 5) {
            array_shift($options);
            $secondField = Arr::first($options);
        }

        $type = Arr::last($options) ?? 'date';

        if (! $startDate || ! $endDate) {
            return $next($request);
        }

        if (! CalHelper::validateDate($startDate) || ! CalHelper::validateDate($endDate)) {
            return $next($request);
        }

        if ($startDate > $endDate) {
            return $next($request);
        }

        $builder = $next($request);

        return $builder->where(function ($q) use ($startDate, $endDate, $field, $secondField, $type) {
            if ($type === 'datetime') {
                $startOfStartDate = Carbon::parse($startDate)->startOfDay();
                $endOfEndDate = Carbon::parse($endDate)->endOfDay();
                $q->where($field, '>=', CalHelper::storeDateTime($startOfStartDate)->toDateTimeString())->where($secondField ?? $field, '<=', CalHelper::storeDateTime($endOfEndDate)->toDateTimeString());
            } else {
                $q->where($field, '>=', $startDate)->where($secondField ?? $field, '<=', $endDate);
            }
        });
    }
}
