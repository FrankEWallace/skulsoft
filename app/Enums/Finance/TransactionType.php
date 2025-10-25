<?php

namespace App\Enums\Finance;

use App\Concerns\HasEnum;
use App\Contracts\HasColor;

enum TransactionType: string implements HasColor
{
    use HasEnum;

    case PAYMENT = 'payment';
    case RECEIPT = 'receipt';
    case TRANSFER = 'transfer';

    public static function translation(): string
    {
        return 'finance.transaction.types.';
    }

    public function color(): string
    {
        return match ($this) {
            self::RECEIPT => 'success',
            self::PAYMENT => 'danger',
            self::TRANSFER => 'info',
        };
    }
}
