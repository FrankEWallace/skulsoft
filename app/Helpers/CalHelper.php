<?php

namespace App\Helpers;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use DateTime;
use Illuminate\Support\Arr;

class CalHelper
{
    protected $timeFormats = [];

    public static function validateDateFormat(?string $date = '', ?string $format = 'Y-m-d'): bool
    {
        if (! $date) {
            return false;
        }

        $value = DateTime::createFromFormat('!'.$format, $date);

        if ($value && $value->format($format) == $date) {
            return true;
        }

        return false;
    }

    /**
     * Validate a date
     */
    public static function validateDate(?string $date = ''): bool
    {
        if (! $date) {
            return false;
        }

        if (preg_match('/[a-zA-Z]/', $date)) {
            return false;
        }

        try {
            Carbon::parse($date);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    private static function getTimezone()
    {
        $defaultTimezone = config('config.system.timezone', config('app.timezone'));

        if (! \Auth::check()) {
            return $defaultTimezone;
        }

        return \Auth::user()->timezone;
    }

    /**
     * Convert date to user's timezone
     */
    public static function toDate(?string $date): ?string
    {
        return $date ? Carbon::parse($date)->timezone(self::getTimezone())->toDateString() : null;
    }

    /**
     * Convert time to user's timezone
     */
    public static function toTime(?string $time): ?string
    {
        return $time ? Carbon::parse($time)->timezone(self::getTimezone())->toTimeString() : null;
    }

    /**
     * Convert datetime to user's timezone
     */
    public static function toDateTime(?string $datetime): ?string
    {
        return $datetime ? Carbon::parse($datetime)->timezone(self::getTimezone())->toDateTimeString() : null;
    }

    /**
     * Convert datetime to UTC before store
     */
    public static function storeDateTime(?string $datetime)
    {
        return $datetime ? Carbon::parse($datetime, self::getTimezone())->timezone(config('app.timezone')) : null;
    }

    public static function getDateFormat()
    {
        if (! \Auth::check()) {
            $momentFormat = config('config.system.date_format');
        } else {
            $momentFormat = Arr::get(\Auth::user()->preference, 'system.date_format', config('config.system.date_format'));
        }

        return match ($momentFormat) {
            'D-MM-YY' => 'j-m-y',
            'D-MM-YYYY' => 'j-m-Y',
            'DD-MM-YYYY' => 'd-m-Y',
            'MM-DD-YYYY' => 'm-d-Y',
            'YYYY-MM-DD' => 'Y-m-d',
            'MMM D, YYYY' => 'M j, Y',
            'MMMM D, YYYY' => 'F j, Y',
            'dddd, MMM D, YYYY' => 'l, M j, Y',
            'dddd, MMMM D, YYYY' => 'l, F j, Y',
            'D MMM YYYY' => 'j M Y',
            'D MMMM YYYY' => 'j F Y',
            'dddd, D MMM YYYY' => 'l, j M Y',
            'dddd, D MMMM YYYY' => 'l, j F Y',
            default => 'd M Y'
        };
    }

    public static function getTimeFormat()
    {
        if (! \Auth::check()) {
            $momentFormat = config('config.system.time_format');
        } else {
            $momentFormat = Arr::get(\Auth::user()->preference, 'system.time_format', config('config.system.time_format'));
        }

        return match ($momentFormat) {
            'H:m' => 'G:i',
            'H:m a' => 'G:i a',
            'H:m A' => 'G:i A',
            'H:mm' => 'G:i',
            'H:mm a' => 'G:i a',
            'H:mm A' => 'G:i A',
            'HH:mm' => 'H:i',
            'HH:mm a' => 'H:i a',
            'HH:mm A' => 'H:i A',
            'h:m' => 'g:i',
            'h:m a' => 'g:i a',
            'h:m A' => 'g:i A',
            'h:mm' => 'g:i',
            'h:mm a' => 'g:i a',
            'h:mm A' => 'g:i A',
            'hh:mm' => 'h:i',
            'hh:mm a' => 'h:i a',
            'hh:mm A' => 'h:i A',
            default => 'H:i a'
        };
    }

    public static function getDateTimeFormat()
    {
        return self::getDateFormat().' '.self::getTimeFormat();
    }

    /**
     * Show date to user's timezone & format
     */
    public static function showDate(?string $date)
    {
        return $date ? Carbon::parse($date)->timezone(self::getTimezone())->format(self::getDateFormat()) : null;
    }

    /**
     * Show date to user's timezone & format
     */
    public static function showDay(?string $date)
    {
        return $date ?
            trans('list.days.'.strtolower(Carbon::parse($date)->timezone(self::getTimezone())->format('l')))
            : null;
    }

    /**
     * Show time to user's timezone & format
     */
    public static function showTime(?string $time)
    {
        return $time ? Carbon::parse($time)->timezone(self::getTimezone())->format(self::getTimeFormat()) : null;
    }

    /**
     * Show date time to user's timezone & format
     */
    public static function showDateTime(?string $datetime)
    {
        return $datetime ? Carbon::parse($datetime)->timezone(self::getTimezone())->format(self::getDateTimeFormat()) : null;
    }

    /**
     * Get date diff
     */
    public static function dateDiff(?string $startDate = null, ?string $endDate = null, bool $increment = true): int
    {
        $startDate = self::validateDate($startDate) ? Carbon::parse($startDate) : today();
        $endDate = self::validateDate($endDate) ? Carbon::parse($endDate) : today();

        if ($increment) {
            return abs($endDate->diffInDays($startDate)) + 1;
        }

        return abs($endDate->diffInDays($startDate));
    }

    /**
     * Get age from date
     */
    public static function getAge(?string $date = null): array
    {
        $age = Carbon::parse($date)->diff(Carbon::now());

        return [
            'years' => $age->y,
            'months' => $age->m,
            'days' => $age->d,
        ];
    }

    /**
     * Get age from date
     */
    public static function getAgeDisplay(?string $date = null): string
    {
        $age = Carbon::parse($date)->diff(Carbon::now());

        return trans('global.age', ['year' => $age->y, 'month' => $age->m, 'day' => $age->d]);
    }

    /**
     * Get age from date
     */
    public static function getAgeShortDisplay(?string $date = null): string
    {
        $age = Carbon::parse($date)->diff(Carbon::now());

        return trans('global.age_short', ['year' => $age->y]);
    }

    /**
     * Get period between date
     */
    public static function getPeriod(?string $startDate = null, ?string $endDate = null): string
    {
        if (! $startDate) {
            return '-';
        }

        if ($endDate) {
            return trans('general.period_between', ['start' => CalHelper::showDate($startDate), 'end' => CalHelper::showDate($endDate)]);
        }

        return trans('general.period_till', ['start' => CalHelper::showDate($startDate)]);
    }

    /**
     * Get duration between date
     */
    public static function getDuration(?string $startDate = null, ?string $endDate = null, string $unit = 'year-month'): string
    {
        if (! $startDate) {
            return '-';
        }

        $endDate = $endDate ?: today()->toDateString();

        if ($unit == 'day') {
            return abs(Carbon::parse($endDate)->diffInDays(Carbon::parse($startDate))) + 1 .' '.trans('list.durations.days');
        }

        return Carbon::parse($endDate)->diff(Carbon::parse($startDate))->format('%y '.trans('list.durations.year').', %m '.trans('list.durations.month'));
    }

    public static function datesInPeriod(string $startDate, string $endDate)
    {
        $period = CarbonPeriod::create($startDate, $endDate);

        $dates = [];

        foreach ($period as $date) {
            $dates[] = $date->format('Y-m-d');
        }

        return $dates;
    }

    /**
     * Get age from date
     */
    public static function randomDate(int $minAge = 25, int $maxAge = 50): string
    {
        return Carbon::today()->subYears(rand($minAge, $maxAge))->subDays(rand(0, 365))->toDateString();
    }
}
