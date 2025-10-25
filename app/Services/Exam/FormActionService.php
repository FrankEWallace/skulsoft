<?php

namespace App\Services\Exam;

use App\Actions\Exam\GetAvailableSubjectForStudent;
use App\Actions\Exam\GetReassessmentSubjectForStudent;
use App\Models\Exam\Form;
use App\Models\Exam\Schedule;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FormActionService
{
    public function updateStatus(Request $request, Form $form): void
    {
        if ($form->approved_at->value && $request->status == 'approve') {
            return;
        }

        if (! $form->approved_at->value && $request->status == 'disapprove') {
            return;
        }

        $form->approved_at = $request->status == 'approve' ? now()->toDateTimeString() : null;
        $form->save();
    }

    public function getStudentDetail(int $id): Student
    {
        if (auth()->user()->hasRole('student')) {
            $student = Student::query()
                ->auth()
                ->first();

            if ($student->id != $id) {
                throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
            }
        }

        return Student::query()
            ->detail()
            ->where('students.id', $id)
            ->first();
    }

    public function getForm(Student $student, Schedule $schedule): Form
    {
        return Form::query()
            ->whereScheduleId($schedule->id)
            ->whereStudentId($student->id)
            ->firstOrFail();
    }

    public function getRecords(Student $student, Schedule $schedule, Form $form): array
    {
        $schedule->load('records', 'exam', 'batch');

        if ($schedule->is_reassessment) {
            $records = (new GetReassessmentSubjectForStudent)->execute($student, $schedule);
        } else {
            $records = (new GetAvailableSubjectForStudent)->execute($student, $schedule);
        }

        return $records;
    }
}
