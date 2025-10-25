<?php

namespace App\Concerns;

use App\Models\Student\Student;
use Illuminate\Validation\ValidationException;

trait StudentAction
{
    public function ensureIsNotTransferred(Student $student, ?string $date = null)
    {
        $date = $date ?? today()->toDateString();

        $admission = $student->admission;

        if (empty($admission->leaving_date->value)) {
            return;
        }

        if ($admission->leaving_date->value >= $date) {
            return;
        }

        throw ValidationException::withMessages(['message' => trans('student.transfer.could_not_perform_if_transferred')]);
    }
}
