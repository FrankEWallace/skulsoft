<?php

namespace App\Enums\Employee;

use App\Concerns\HasEnum;
use App\Contracts\HasColor;

enum Status: string implements HasColor
{
    use HasEnum;

    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    public static function translation(): string
    {
        return 'employee.statuses.';
    }

    public function color(): string
    {
        return match ($this) {
            Status::ACTIVE => 'success',
            Status::INACTIVE => 'danger',
        };
    }
}
