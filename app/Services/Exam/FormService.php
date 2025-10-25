<?php

namespace App\Services\Exam;

use App\Enums\Exam\AssessmentAttempt;
use App\Http\Resources\Exam\ExamResource;
use App\Models\Exam\Exam;
use App\Models\Exam\Form;
use App\Models\Exam\Schedule;
use App\Models\Student\Student;
use Illuminate\Http\Request;

class FormService
{
    public function preRequisite(Request $request)
    {
        $exams = ExamResource::collection(Exam::query()
            ->with('term.division')
            ->byPeriod()
            ->get());

        $attempts = AssessmentAttempt::getOptions();

        return compact('exams', 'attempts');
    }

    public function getFormSubmissionData(Schedule $schedule)
    {
        if (! auth()->user()->hasRole('student')) {
            return;
        }

        if (! $schedule->has_form) {
            return;
        }

        $student = Student::query()
            ->auth()
            ->first();

        $form = Form::query()
            ->where('schedule_id', $schedule->id)
            ->where('student_id', $student->id)
            ->first();

        $schedule->submitted_at = $form?->submitted_at;
        $schedule->approved_at = $form?->approved_at;

        return $schedule;
    }

    public function deletable(Form $form): bool
    {
        return true;
    }

    public function delete(Form $form)
    {
        $form->delete();
    }
}
