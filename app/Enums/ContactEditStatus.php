<?php

namespace App\Enums;

use App\Concerns\HasEnum;
use App\Contracts\HasColor;

enum ContactEditStatus: string implements HasColor
{
    use HasEnum;

    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public static function translation(): string
    {
        return 'contact.edit_request.statuses.';
    }

    public function color(): string
    {
        return match ($this) {
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
            self::PENDING => 'info',
        };
    }
}
