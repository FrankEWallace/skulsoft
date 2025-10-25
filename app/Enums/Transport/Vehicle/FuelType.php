<?php

namespace App\Enums\Transport\Vehicle;

use App\Concerns\HasEnum;

enum FuelType: string
{
    use HasEnum;

    case PETROL = 'petrol';
    case DIESEL = 'diesel';
    case CNG = 'cng';
    case ELECTRICAL = 'electrical';

    public static function translation(): string
    {
        return 'transport.vehicle.fuel_types.';
    }
}
