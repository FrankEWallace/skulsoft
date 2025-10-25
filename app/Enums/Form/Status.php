<?php

namespace App\Enums\Form;

use App\Concerns\HasEnum;
use App\Contracts\HasColor;

enum Status: string implements HasColor
{
    use HasEnum;

    case PUBLISHED = 'published';
    case DRAFT = 'draft';
    case EXPIRED = 'expired';

    public static function translation(): string
    {
        return 'form.statuses.';
    }

    public function color(): string
    {
        return match ($this) {
            Status::PUBLISHED => 'success',
            Status::DRAFT => 'info',
            Status::EXPIRED => 'danger',
        };
    }
}
