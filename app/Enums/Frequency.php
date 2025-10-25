<?php

namespace App\Enums;

use App\Concerns\HasEnum;

enum Frequency: string
{
    use HasEnum;

    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case FORTNIGHTLY = 'fortnightly';
    case MONTHLY = 'monthly';
    case BI_MONTHLY = 'bi_monthly';
    case QUARTERLY = 'quarterly';
    case BI_ANNUALLY = 'bi_annually';
    case ANNUALLY = 'annually';

    public static function translation(): string
    {
        return 'list.frequencies.';
    }
}
