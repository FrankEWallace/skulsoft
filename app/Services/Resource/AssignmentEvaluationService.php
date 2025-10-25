<?php

namespace App\Services\Resource;

use App\Models\Resource\Assignment;
use App\Models\Resource\AssignmentSubmission;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class AssignmentEvaluationService
{
    public function evaluate(Request $request, Assignment $assignment): void
    {
        $batchIds = $assignment->records()->pluck('batch_id');

        $students = Student::query()
            ->whereIn('batch_id', $batchIds)
            ->get();

        foreach ($request->students as $data) {
            $student = $students->firstWhere('uuid', Arr::get($data, 'uuid'));
            if (! $student) {
                continue;
            }

            $submission = AssignmentSubmission::query()
                ->where('assignment_id', $assignment->id)
                ->where('student_id', $student->id)
                ->first();

            if (! $submission) {
                continue;
            }

            $submission->obtained_mark = Arr::get($data, 'obtained_mark');
            $submission->comment = Arr::get($data, 'comment');
            $submission->save();
        }
    }
}
