<?php

namespace App\Enums\Approval;

use App\Concerns\HasEnum;

enum Category: string
{
    use HasEnum;

    case ITEM_BASED = 'item_based';
    case PAYMENT_BASED = 'payment_based';
    case CONTACT_BASED = 'contact_based';
    case OTHER = 'other';

    public static function translation(): string
    {
        return 'approval.categories.';
    }
}
