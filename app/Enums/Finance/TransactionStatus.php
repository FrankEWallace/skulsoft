<?php

namespace App\Enums\Finance;

use App\Concerns\HasEnum;
use App\Contracts\HasColor;

enum TransactionStatus: string implements HasColor
{
    use HasEnum;

    case PENDING = 'pending';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case REJECTED = 'rejected';
    case SUCCEED = 'succeed';

    public static function translation(): string
    {
        return 'finance.transaction_statuses.';
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'info',
            self::FAILED => 'danger',
            self::CANCELLED => 'warning',
            self::REJECTED => 'warning',
            self::SUCCEED => 'success',
        };
    }
}
