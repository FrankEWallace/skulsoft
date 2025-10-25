<?php

namespace App\Enums\Student;

use App\Concerns\HasEnum;
use App\Contracts\HasColor;

enum RegistrationStatus: string implements HasColor
{
    use HasEnum;

    case INITIATED = 'initiated';
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public static function translation(): string
    {
        return 'student.registration.statuses.';
    }

    public function color(): string
    {
        return match ($this) {
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
            self::PENDING => 'info',
            self::INITIATED => 'info',
        };
    }
}
