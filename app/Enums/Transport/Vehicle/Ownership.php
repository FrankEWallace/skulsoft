<?php

namespace App\Enums\Transport\Vehicle;

use App\Concerns\HasEnum;

enum Ownership: string
{
    use HasEnum;

    case OWNED = 'owned';
    case RENTED = 'rented';

    public static function translation(): string
    {
        return 'transport.vehicle.ownerships.';
    }
}
