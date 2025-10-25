<?php

namespace App\Enums\Academic;

use App\Concerns\HasEnum;

enum CertificateFor: string
{
    use HasEnum;

    case STUDENT = 'student';
    case EMPLOYEE = 'employee';

    public static function translation(): string
    {
        return 'academic.certificate.for.';
    }

    public function variable(): array
    {
        return match ($this) {
            self::STUDENT => [
                'variables' => '#NAME#, #DOB#, #DOB_IN_WORDS#, #GENDER#, #FATHER_NAME#, #MOTHER_NAME#, #NATIONALITY#, #CATEGORY#, #CASTE#, #RELIGION#, #ADDRESS#, #ADMISSION_NUMBER#, #ADMISSION_DATE#, #TRANSFER_DATE#, #COURSE_BATCH#, #ROLL_NUMBER#, #PRESENT_DAYS#, #ABSENT_DAYS#, #WORKING_DAYS#, #SUBJECT_STUDYING#, #FEE_CONCESSION_AVAILED#, #FEE_TOTAL#, #FEE_PAID#, #FEE_BALANCE#, #CERTIFICATE_DATE#, #CERTIFICATE_NUMBER#',
            ],
            self::EMPLOYEE => [
                'variables' => '#NAME#, #DOB#, #DOB_IN_WORDS#, #GENDER#, #FATHER_NAME#, #MOTHER_NAME#, #NATIONALITY#, #CATEGORY#, #CASTE#, #RELIGION#, #ADDRESS#, #EMPLOYEE_CODE#, #JOINING_DATE#, #LEAVING_DATE#, #DESIGNATION#, #DEPARTMENT#, #CERTIFICATE_DATE#, #CERTIFICATE_NUMBER#',
            ],
            default => []
        };
    }

    public static function getOptions(): array
    {
        $options = [];

        foreach (self::cases() as $option) {
            $variables = $option->variable()['variables'] ?? '';

            $options[] = ['label' => trans(self::translation().$option->value), 'value' => $option->value, 'variables' => $variables];
        }

        return $options;
    }
}
