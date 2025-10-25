<?php

namespace App\Enums\Discipline;

use App\Concerns\HasEnum;
use App\Contracts\HasColor;

enum IncidentSeverity: string implements HasColor
{
    use HasEnum;

    case MINOR = 'minor';
    case MAJOR = 'major';
    case CRITICAL = 'critical';

    public static function translation(): string
    {
        return 'discipline.incident.severities.';
    }

    public function color(): string
    {
        return match ($this) {
            self::MINOR => 'info',
            self::MAJOR => 'warning',
            self::CRITICAL => 'danger',
        };
    }
}
