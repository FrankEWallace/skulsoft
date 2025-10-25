<?php

namespace App\ValueObjects;

use App\Helpers\CalHelper;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class Cal
{
    public string $type;

    public string $value;

    public string $formatted;

    public string $at = '';

    public array $age = [];

    public string $diffForHuman = '';

    public function __construct(mixed $date = '', string $type = 'date')
    {
        $this->type = $type;

        $this->value = $this->getValue($date);

        $this->formatted = $this->getFormattedValue();

        if (! empty($this->value)) {

            if ($this->type == 'date') {
                $this->age = $this->getAge();
            } elseif ($this->type == 'time') {
                $this->at = $this->toTime();
            } elseif ($this->type == 'datetime') {
                $this->at = $this->toDateTime();
            }

            $this->diffForHuman = Carbon::parse($this->value)->diffForHumans();
        }
    }

    public static function from(mixed $date = '', $type = 'date'): self
    {
        return new self($date, $type);
    }

    public static function date(mixed $date = ''): self
    {
        return new self($date, 'date');
    }

    public static function month(mixed $date = ''): self
    {
        return new self($date, 'month');
    }

    public static function dayMonth(mixed $date = ''): self
    {
        return new self($date, 'dayMonth');
    }

    public static function time(mixed $date = ''): self
    {
        return new self($date, 'time');
    }

    public static function dateTime(mixed $date = ''): self
    {
        return new self($date, 'datetime');
    }

    public function getValue(mixed $date): string
    {
        if (empty($date)) {
            return '';
        }

        if ($date instanceof self) {
            $date = $date->value;
        }

        if (! CalHelper::validateDate($date)) {
            return '';
        }

        if ($this->type == 'time') {
            return Carbon::parse($date)->toTimeString();
        } elseif ($this->type == 'datetime') {
            return Carbon::parse($date)->toDateTimeString();
        } elseif ($this->type == 'month') {
            return Carbon::parse($date)->format('Y-m');
        }

        return Carbon::parse($date)->toDateString();
    }

    public function getFormattedValue(): string
    {
        if (empty($this->value)) {
            return '';
        }

        if ($this->type == 'time') {
            return $this->showTime();
        } elseif ($this->type == 'datetime') {
            return $this->showDateTime();
        } elseif ($this->type == 'month') {
            return $this->showMonth();
        } elseif ($this->type == 'dayMonth') {
            return $this->showDayMonth();
        }

        return $this->showDate();
    }

    public function getTimezone()
    {
        $defaultTimezone = config('config.system.timezone', config('app.timezone'));

        if (empty(auth()->check())) {
            return $defaultTimezone;
        }

        return auth()->user()->timezone;
    }

    public function toDate(): ?string
    {
        return Carbon::parse($this->value)->timezone(self::getTimezone())->toDateString();
    }

    public function toTime(): ?string
    {
        return Carbon::parse($this->value)->timezone(self::getTimezone())->toTimeString();
    }

    public function toDateTime(): ?string
    {
        return Carbon::parse($this->value)->timezone(self::getTimezone())->toDateTimeString();
    }

    public function getDateFormat()
    {
        if (empty(auth()->check())) {
            $momentFormat = config('config.system.date_format');
        } else {
            $momentFormat = Arr::get(auth()->user()->preference, 'system.date_format', config('config.system.date_format'));
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

    public function getTimeFormat()
    {
        if (empty(auth()->check())) {
            $momentFormat = config('config.system.time_format');
        } else {
            $momentFormat = Arr::get(auth()->user()->preference, 'system.time_format', config('config.system.time_format'));
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

    public function getDateTimeFormat()
    {
        return self::getDateFormat().' '.self::getTimeFormat();
    }

    public function showDate()
    {
        return Carbon::parse($this->value)->format(self::getDateFormat());
    }

    public function showTime()
    {
        return Carbon::parse($this->value)->timezone(self::getTimezone())->format(self::getTimeFormat());
    }

    public function showDateTime()
    {
        return Carbon::parse($this->value)->timezone(self::getTimezone())->format(self::getDateTimeFormat());
    }

    public function showMonth()
    {
        return Carbon::parse($this->value)->timezone(self::getTimezone())->format('F Y');
    }

    public function showDayMonth()
    {
        return Carbon::parse($this->value)->timezone(self::getTimezone())->format('F j');
    }

    public function getAge(): array
    {
        $age = Carbon::parse($this->value)->diff(Carbon::now());

        return [
            'display' => trans('global.age', ['year' => $age->y, 'month' => $age->m, 'day' => $age->d]),
            'short' => trans('global.age_short', ['year' => $age->y]),
            'years' => $age->y,
            'months' => $age->m,
            'days' => $age->d,
        ];
    }

    public function toIso8601String(): string
    {
        return Carbon::parse($this->value)->timezone(self::getTimezone())->toIso8601String();
    }

    public function carbon(): Carbon
    {
        return Carbon::parse($this->value)->timezone(self::getTimezone());
    }
}
