<?php

namespace App\Services\Finance\Report;

use App\Enums\Student\StudentStatus;

class FeeRefundService
{
    public function preRequisite(): array
    {
        $statuses = StudentStatus::getOptions();

        return compact('statuses');
    }
}
