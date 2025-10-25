<?php

namespace App\QueryFilters;

use App\Helpers\CalHelper;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class MonthDateBetween
{
    public function handle($request, Closure $next, ...$options)
    {
        if (count($options) != 3) {
            return $next($request);
        }

        $startDate = request()->query(Arr::first($options));
        array_shift($options);

        $endDate = request()->query(Arr::first($options));
        array_shift($options);

        $field = Arr::first($options);

        if (! $startDate || ! $endDate) {
            return $next($request);
        }

        if (! CalHelper::validateDate($startDate) || ! CalHelper::validateDate($endDate)) {
            return $next($request);
        }

        $carbonStartDate = Carbon::parse($startDate);
        $carbonEndDate = Carbon::parse($endDate);

        if (abs($carbonStartDate->diffInDays($carbonEndDate)) > 30) {
            throw ValidationException::withMessages(['message' => trans('general.errors.max_period_in_days', ['attribute' => 30])]);
        }

        $carbonStartMonth = $carbonStartDate->format('m');
        $carbonEndMonth = $carbonEndDate->format('m');

        $partial = false;
        if ($carbonStartMonth > $carbonEndMonth) {
            $partial = true;
            $carbonFirstStartDate = Carbon::parse($startDate);
            $carbonFirstEndDate = Carbon::parse($startDate)->endOfYear();
            $carbonSecondStartDate = Carbon::parse($startDate)->startOfYear();
            $carbonSecondEndDate = Carbon::parse($endDate);
        }

        $builder = $next($request);

        if ($partial) {
            return $builder->where(function ($q) use ($carbonFirstStartDate, $carbonFirstEndDate, $carbonSecondStartDate, $carbonSecondEndDate, $field) {
                $q->where(function ($q1) use ($carbonFirstStartDate, $carbonFirstEndDate, $field) {
                    $q1->whereRaw('date_format('.$field.', "%m-%d") between ? and ?', [
                        $carbonFirstStartDate->format('m-d'),
                        $carbonFirstEndDate->format('m-d'),
                    ]);
                })->orWhere(function ($q1) use ($carbonSecondStartDate, $carbonSecondEndDate, $field) {
                    $q1->whereRaw('date_format('.$field.', "%m-%d") between ? and ?', [
                        $carbonSecondStartDate->format('m-d'),
                        $carbonSecondEndDate->format('m-d'),
                    ]);
                });
            });
        }

        return $builder->where(function ($q) use ($carbonStartDate, $carbonEndDate, $field) {
            $q->whereRaw('date_format('.$field.', "%m-%d") between ? and ?', [
                $carbonStartDate->format('m-d'),
                $carbonEndDate->format('m-d'),
            ]);
        });
    }
}
