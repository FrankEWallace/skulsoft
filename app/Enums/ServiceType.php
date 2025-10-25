<?php

namespace App\Enums;

use App\Concerns\HasEnum;

enum ServiceType: string
{
    use HasEnum;

    case MESS = 'mess';
    case TRANSPORT = 'transport';
    case HOSTEL = 'hostel';

    public static function translation(): string
    {
        return 'service.types.';
    }

    public function color(): string
    {
        return match ($this) {
            self::MESS => 'success',
            self::TRANSPORT => 'info',
            self::HOSTEL => 'primary',
        };
    }

    public static function getIcon($type): string
    {
        return match ($type) {
            self::MESS->value => 'fas fa-utensils',
            self::TRANSPORT->value => 'fas fa-bus',
            self::HOSTEL->value => 'fas fa-hotel',
        };
    }
}
