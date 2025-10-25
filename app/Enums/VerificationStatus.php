<?php

namespace App\Enums;

use App\Concerns\HasEnum;
use App\Contracts\HasColor;

enum VerificationStatus: string implements HasColor
{
    use HasEnum;

    case PENDING = 'pending';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';

    public static function translation(): string
    {
        return 'contact.verification.statuses.';
    }

    public function color(): string
    {
        return match ($this) {
            self::VERIFIED => 'success',
            self::REJECTED => 'danger',
            self::PENDING => 'info',
        };
    }
}
