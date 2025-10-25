<?php

namespace App\Services\Library\Report;

class TopBorrowerService
{
    public function preRequisite(): array
    {
        $issuedTo = [
            ['label' => trans('student.student'), 'value' => 'student'],
            ['label' => trans('employee.employee'), 'value' => 'employee'],
        ];

        return compact('issuedTo');
    }
}
