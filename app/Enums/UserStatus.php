<?php

namespace App\Enums;

use App\Concerns\HasEnum;
use App\Contracts\HasColor;

enum UserStatus: string implements HasColor
{
    use HasEnum;

    case ACTIVATED = 'activated';
    case BANNED = 'banned';
    case DISAPPROVED = 'disapproved';
    case PENDING_VERIFICATION = 'pending_verification';
    case PENDING_APPROVAL = 'pending_approval';

    public static function translation(): string
    {
        return 'user.statuses.';
    }

    public function color(): string
    {
        return match ($this) {
            UserStatus::ACTIVATED => 'success',
            UserStatus::BANNED => 'danger',
            UserStatus::DISAPPROVED => 'danger',
            UserStatus::PENDING_VERIFICATION => 'warning',
            UserStatus::PENDING_APPROVAL => 'info',
        };
    }
}
