<?php

namespace App\Enums\Reception;

use App\Concerns\HasEnum;

enum GatePassStatus: string
{
    use HasEnum;

    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public static function translation(): string
    {
        return 'reception.gate_pass.statuses.';
    }
}
