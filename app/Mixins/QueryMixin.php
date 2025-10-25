<?php

namespace App\Mixins;

class QueryMixin
{
    public function whereLike()
    {
        return function ($key, $value) {
            return $this->where($key, 'like', '%'.$value.'%');
        };
    }

    public function whereDateBetween()
    {
        return function (string $startDate, string $endDate, string $field, ?string $secondField = null) {
            return $this->where(function ($q) use ($startDate, $endDate, $field, $secondField) {
                $q->where($field, '>=', $startDate)->where($secondField ?? $field, '<=', $endDate);
            });
        };
    }

    public function whereOverlapping()
    {
        return function (string $startDate, string $endDate, string $startField = 'start_date', string $endField = 'end_date') {
            return $this->where(function ($q) use ($startDate, $endDate, $startField, $endField) {
                $q->where(function ($q) use ($startDate, $startField, $endField) {
                    $q->where($startField, '<=', $startDate)->where($endField, '>=', $startDate);
                })->orWhere(function ($q) use ($endDate, $startField, $endField) {
                    $q->where($startField, '<=', $endDate)->where($endField, '>=', $endDate);
                })->orWhere(function ($q) use ($startDate, $endDate, $startField, $endField) {
                    $q->where($startField, '>=', $startDate)->where($endField, '<=', $endDate);
                });
            });
        };
    }
}
