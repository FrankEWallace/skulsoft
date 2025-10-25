<?php

namespace App\Enums;

use App\Concerns\HasEnum;

enum DeviceType: string
{
    use HasEnum;

    case BIOMETRIC_ATTENDANCE = 'biometric_attendance';
}
