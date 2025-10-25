<?php

namespace App\Enums\Finance;

use App\Concerns\HasEnum;

enum DefaultCustomFeeType: string
{
    use HasEnum;

    case SUBJECT_WISE_EXAM_FEE = 'subject_wise_exam_fee';
    case EXAM_FORM_FEE = 'exam_form_fee';
    case EXAM_FORM_LATE_FEE = 'exam_form_late_fee';
    case LIBRARY_CHARGE = 'library_charge';

    public static function translation(): string
    {
        return 'finance.fee.default_custom_fee_types.';
    }
}
