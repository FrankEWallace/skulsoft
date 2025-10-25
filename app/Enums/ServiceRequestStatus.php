<?php

namespace App\Enums;

use App\Concerns\HasEnum;
use App\Contracts\HasColor;

enum ServiceRequestStatus: string implements HasColor
{
    use HasEnum;

    case REQUESTED = 'requested';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public static function translation(): string
    {
        return 'service.request.statuses.';
    }

    public function color(): string
    {
        return match ($this) {
            ServiceRequestStatus::REQUESTED => 'info',
            ServiceRequestStatus::APPROVED => 'success',
            ServiceRequestStatus::REJECTED => 'danger',
        };
    }
}
