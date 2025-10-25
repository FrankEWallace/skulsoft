<?php

namespace App\Enums\Finance;

use App\Concerns\HasEnum;
use Carbon\Carbon;

enum LateFeeFrequency: string
{
    use HasEnum;

    case ONE_TIME = 'one_time';
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case FORTNIGHTLY = 'fortnightly';
    case MONTHLY = 'monthly';
    case BI_MONTHLY = 'bi_monthly';
    case QUARTERLY = 'quarterly';
    case BI_ANNUALLY = 'bi_annually';
    case ANNUALLY = 'annually';

    public static function getMultiplier($value, $startDate, $dueDate): int
    {
        $frequency = self::tryFrom($value);

        if (! $frequency) {
            return 0;
        }

        $startDate = Carbon::parse($startDate);
        $dueDate = Carbon::parse($dueDate);

        return match ($frequency) {
            LateFeeFrequency::ONE_TIME => 1,
            LateFeeFrequency::DAILY => $startDate->diffInDays($dueDate),
            LateFeeFrequency::WEEKLY => $startDate->diffInWeeks($dueDate),
            LateFeeFrequency::FORTNIGHTLY => intdiv($startDate->diffInWeeks($dueDate), 2),
            LateFeeFrequency::MONTHLY => $startDate->diffInMonths($dueDate),
            LateFeeFrequency::BI_MONTHLY => intdiv($startDate->diffInMonths($dueDate), 2),
            LateFeeFrequency::QUARTERLY => intdiv($startDate->diffInMonths($dueDate), 3),
            LateFeeFrequency::BI_ANNUALLY => intdiv($startDate->diffInMonths($dueDate), 6),
            LateFeeFrequency::ANNUALLY => $startDate->diffInYears($dueDate),
        };
    }

    public function getDuration(): array
    {
        return match ($this) {
            LateFeeFrequency::ONE_TIME => ['value' => 0, 'period' => 'addDay'],
            LateFeeFrequency::DAILY => ['value' => 1, 'period' => 'addDay'],
            LateFeeFrequency::WEEKLY => ['value' => 1, 'period' => 'addWeek'],
            LateFeeFrequency::FORTNIGHTLY => ['value' => 2, 'period' => 'addWeek'],
            LateFeeFrequency::MONTHLY => ['value' => 1, 'period' => 'addMonth'],
            LateFeeFrequency::BI_MONTHLY => ['value' => 2, 'period' => 'addMonths'],
            LateFeeFrequency::QUARTERLY => ['value' => 3, 'period' => 'addMonths'],
            LateFeeFrequency::BI_ANNUALLY => ['value' => 6, 'period' => 'addMonths'],
            LateFeeFrequency::ANNUALLY => ['value' => 1, 'period' => 'addYear'],
        };
    }

    public static function translation(): string
    {
        return 'finance.late_fee_frequencies.';
    }
}
