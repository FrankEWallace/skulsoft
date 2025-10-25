<?php

namespace App\Enums\Site;

use App\Concerns\HasEnum;

enum MenuPlacement: string
{
    use HasEnum;

    case HEADER = 'header';
    case FOOTER = 'footer';
    case OTHER = 'other';

    public static function translation(): string
    {
        return 'site.menu.placements.';
    }
}
