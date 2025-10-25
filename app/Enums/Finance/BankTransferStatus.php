<?php

namespace App\Enums\Finance;

use App\Concerns\HasEnum;
use App\Contracts\HasColor;

enum BankTransferStatus: string implements HasColor
{
    use HasEnum;

    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public static function translation(): string
    {
        return 'finance.bank_transfer.statuses.';
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'info',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
        };
    }
}
