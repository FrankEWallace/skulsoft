<?php

namespace App\Services\Academic;

use App\Models\Academic\Subject;
use App\Models\Academic\SubjectRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class SubjectActionService
{
    public function updateFee(Request $request, Subject $subject): void
    {
        $request->validate([
            'course_fee' => 'nullable|integer|min:0',
            'exam_fee' => 'required|integer|min:0',
        ]);

        $courseFee = $request->course_fee ?? 0;
        $examFee = $request->exam_fee ?? 0;

        SubjectRecord::query()
            ->where('subject_id', $subject->id)
            ->update([
                'course_fee' => $courseFee,
                'exam_fee' => $examFee,
            ]);
    }

    public function reorder(Request $request): void
    {
        $subjects = $request->subjects ?? [];

        $allSubjects = Subject::query()
            ->byPeriod()
            ->get();

        foreach ($subjects as $index => $subjectItem) {
            $subject = $allSubjects->firstWhere('uuid', Arr::get($subjectItem, 'uuid'));

            if (! $subject) {
                continue;
            }

            $subject->position = $index + 1;
            $subject->save();
        }
    }
}
