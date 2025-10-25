<?php

namespace App\Enums\Student;

use App\Concerns\HasEnum;
use App\Contracts\HasColor;

enum TransferRequestStatus: string implements HasColor
{
    use HasEnum;

    case REQUESTED = 'requested';
    case IN_PROGRESS = 'in_progress';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public static function translation(): string
    {
        return 'student.transfer_request.statuses.';
    }

    public function color(): string
    {
        return match ($this) {
            self::REQUESTED => 'info',
            self::IN_PROGRESS => 'info',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
        };
    }
}
