<?php

namespace App\Enums\Reception;

use App\Concerns\HasEnum;

enum CorrespondenceMode: string
{
    use HasEnum;

    case BY_PERSON = 'by_person';
    case BY_POST = 'by_post';
    case EMAIL = 'email';
    case WHATSAPP = 'whatsapp';

    public static function translation(): string
    {
        return 'reception.correspondence.modes.';
    }
}
