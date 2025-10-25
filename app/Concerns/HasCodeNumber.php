<?php

namespace App\Concerns;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

trait HasCodeNumber
{
    public static function bootHasCodeNumber() {}

    public function scopeCodeNumber(Builder $query, string $numberFormat, string $date, string $field = 'date'): int
    {
        if (Str::of($numberFormat)->contains(['%DAY%', '%DAY_SHORT%'])) {
            $codeNumber = $query->whereNumberFormat($numberFormat)->where($field, $date)->count() + 1;
        } elseif (Str::of($numberFormat)->contains(['%MONTH%', '%MONTH_NUMBER%', '%MONTH_NUMBER_SHORT%', '%MONTH_SHORT%'])) {
            $codeNumber = $query->whereNumberFormat($numberFormat)->whereMonth($field, Carbon::parse($date)->month)->whereYear($field, Carbon::parse($date)->year)->count() + 1;
        } elseif (Str::of($numberFormat)->contains(['%YEAR%', '%YEAR_SHORT%'])) {
            $codeNumber = $query->whereNumberFormat($numberFormat)->whereYear($field, Carbon::parse($date)->year)->count() + 1;
        } else {
            $codeNumber = $query->whereNumberFormat($numberFormat)->max('number') + 1;
        }

        return $codeNumber;
    }

    public function getNumberFromFormat(string $string, string $format = '', string $placeholder = '%NUMBER%')
    {
        if (! Str::contains($format, $placeholder)) {
            return null;
        }

        $prefix = Str::before($format, $placeholder);
        $suffix = Str::after($format, $placeholder);

        $formattedString = Str::after($string, $prefix);
        $number = Str::before($formattedString, $suffix);

        if (! ctype_digit($number)) {
            return null;
        }

        if ($prefix.$number.$suffix !== $string) {
            return null;
        }

        return (int) $number;
    }
}
