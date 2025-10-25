<?php

namespace App\Concerns;

use App\Models\Student\Student;

trait StudentAccess
{
    public function getAccessibleStudentIds()
    {
        return Student::query()
            ->filterAccessible()
            ->pluck('students.id')
            ->all();
    }
}
