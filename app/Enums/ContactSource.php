<?php

namespace App\Enums;

use App\Concerns\HasEnum;

enum ContactSource: string
{
    use HasEnum;

    case VISITOR = 'visitor';
    case GUARDIAN = 'guardian';
    case EMPLOYEE = 'employee';
    case ENQUIRY = 'enquiry';
    case JOB_APPLICANT = 'job_applicant';
    case STUDENT = 'student';
    case ONLINE_REGISTRATION = 'online_registration';
    case CANDIDATE = 'candidate';

    public static function translation(): string
    {
        return 'contact.sources.';
    }
}
