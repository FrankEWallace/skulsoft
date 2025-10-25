<?php

namespace App\Enums\Academic;

use App\Concerns\HasEnum;

enum CertificateType: string
{
    use HasEnum;

    case TRANSFER_CERTIFICATE = 'transfer_certificate';
    case OTHER = 'other';

    public static function translation(): string
    {
        return 'academic.certificate.types.';
    }
}
