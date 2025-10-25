<?php

namespace App\Enums\Reception;

use App\Concerns\HasEnum;

enum EnquiryNature: string
{
    use HasEnum;

    case ADMISSION = 'admission';
    case OTHER = 'other';

    public static function translation(): string
    {
        return 'reception.enquiry.natures.';
    }
}
