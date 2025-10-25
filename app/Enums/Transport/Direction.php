<?php

namespace App\Enums\Transport;

use App\Concerns\HasEnum;

enum Direction: string
{
    use HasEnum;

    case ARRIVAL = 'arrival';
    case DEPARTURE = 'departure';
    case ROUND_TRIP = 'roundtrip';

    public static function translation(): string
    {
        return 'transport.circle.directions.';
    }
}
