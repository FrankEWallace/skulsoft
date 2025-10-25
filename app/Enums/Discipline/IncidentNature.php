<?php

namespace App\Enums\Discipline;

use App\Concerns\HasEnum;
use App\Contracts\HasColor;

enum IncidentNature: string implements HasColor
{
    use HasEnum;

    case POSITIVE = 'positive';
    case NEGATIVE = 'negative';

    public static function translation(): string
    {
        return 'discipline.incident.natures.';
    }

    public function color(): string
    {
        return match ($this) {
            self::POSITIVE => 'success',
            self::NEGATIVE => 'danger',
        };
    }
}
