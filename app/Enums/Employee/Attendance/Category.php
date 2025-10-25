<?php

namespace App\Enums\Employee\Attendance;

use App\Concerns\HasEnum;
use App\Contracts\HasColor;

enum Category: string implements HasColor
{
    use HasEnum;

    case PRESENT = 'present';
    case HOLIDAY = 'holiday';
    case ABSENT = 'absent';
    case HALF_DAY = 'half_day';
    case PRODUCTION_EARNING = 'production_based_earning';
    case PRODUCTION_DEDUCTION = 'production_based_deduction';

    public static function translation(): string
    {
        return 'employee.attendance.categories.';
    }

    public static function productionBased(): array
    {
        return [self::PRODUCTION_EARNING->value, self::PRODUCTION_DEDUCTION->value];
    }

    public static function isProductionBased(string $category): bool
    {
        if (in_array($category, [self::PRODUCTION_EARNING->value, self::PRODUCTION_DEDUCTION->value])) {
            return true;
        }

        return false;
    }

    public static function getProductionBasedOptions(): array
    {
        $options = [];

        foreach (self::cases() as $option) {
            if (self::isProductionBased($option->value)) {
                $options[] = ['label' => trans(self::translation().$option->value), 'value' => $option->value];
            }
        }

        return $options;
    }

    public function color(): string
    {
        return match ($this) {
            self::PRESENT => 'success',
            self::HOLIDAY => 'info',
            self::ABSENT => 'danger',
            self::HALF_DAY => 'warning',
            default => 'secondary'
        };
    }
}
