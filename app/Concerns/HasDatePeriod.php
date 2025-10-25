<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasDatePeriod
{
    public static function bootHasDatePeriod()
    {
        //
    }

    public function scopeBetweenPeriod(Builder $query, string $startDate, string $endDate, string $startField = 'start_date', string $endField = 'end_date')
    {
        $query->where(function ($q) use ($startDate, $endDate, $startField, $endField) {
            $q->where(function ($q) use ($startDate, $startField, $endField) {
                $q->where($startField, '<=', $startDate)->where($endField, '>=', $startDate);
            })->orWhere(function ($q) use ($endDate, $startField, $endField) {
                $q->where($startField, '<=', $endDate)->where($endField, '>=', $endDate);
            })->orWhere(function ($q) use ($startDate, $endDate, $startField, $endField) {
                $q->where($startField, '>=', $startDate)->where($endField, '<=', $endDate);
            });
        });
    }
}
