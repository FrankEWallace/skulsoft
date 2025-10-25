<?php

namespace App\Enums\Student;

use App\Concerns\HasEnum;
use App\Contracts\HasColor;

enum LeaveRequestStatus: string implements HasColor
{
    use HasEnum;

    case REQUESTED = 'requested';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public static function translation(): string
    {
        return 'student.leave_request.statuses.';
    }

    public function color(): string
    {
        return match ($this) {
            self::REQUESTED => 'info',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
        };
    }
}
