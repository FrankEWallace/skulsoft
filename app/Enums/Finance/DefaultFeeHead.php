<?php

namespace App\Enums\Finance;

use App\Concerns\HasEnum;

enum DefaultFeeHead: string
{
    use HasEnum;

    case LATE_FEE = 'late_fee';
    case TRANSPORT_FEE = 'transport_fee';
    case ADDITIONAL_CHARGE = 'additional_charge';
    case ADDITIONAL_DISCOUNT = 'additional_discount';

    public static function translation(): string
    {
        return 'finance.fee.default_fee_heads.';
    }
}
