<?php

namespace App\Enums;

use App\Concerns\HasEnum;

enum Day: string
{
    use HasEnum;

    case MONDAY = 'monday';
    case TUESDAY = 'tuesday';
    case WEDNESDAY = 'wednesday';
    case THURSDAY = 'thursday';
    case FRIDAY = 'friday';
    case SATURDAY = 'saturday';
    case SUNDAY = 'sunday';

    public static function translation(): string
    {
        return 'list.days.';
    }

    public static function getNumberValues(array|string $days = []): array
    {
        $items = [];

        if (is_string($days)) {
            $days = explode(',', $days);
        }

        foreach ($days as $day) {
            $items[] = self::tryFrom($day)->getNumberValue();
        }

        return $items;
    }

    public static function getDayValue($day): self
    {
        return match ($day) {
            1 => self::MONDAY,
            2 => self::TUESDAY,
            3 => self::WEDNESDAY,
            4 => self::THURSDAY,
            5 => self::FRIDAY,
            6 => self::SATURDAY,
            7 => self::SUNDAY
        };
    }

    public function getNumberValue(): int
    {
        return match ($this) {
            self::MONDAY => 1,
            self::TUESDAY => 2,
            self::WEDNESDAY => 3,
            self::THURSDAY => 4,
            self::FRIDAY => 5,
            self::SATURDAY => 6,
            self::SUNDAY => 7
        };
    }
}
