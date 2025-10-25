<?php

namespace App\Services\Form;

use App\Models\Form\Form;
use App\Models\Form\Submission;
use Illuminate\Validation\ValidationException;

class FormSubmissionService
{
    public function findByUuidOrFail(Form $form, string $uuid): Submission
    {
        return Submission::query()
            ->with([
                'model.contact',
                'records.field',
            ])
            ->select(
                'form_submissions.*',
                'admissions.code_number as admission_number',
                'batches.name as batch_name',
                'courses.name as course_name',
                'courses.term as course_term',
                'employees.code_number as employee_code'
            )
            ->whereFormId($form->id)
            ->leftJoin('students', function ($join) {
                $join->on('form_submissions.model_id', '=', 'students.id')
                    ->where('form_submissions.model_type', '=', 'Student');
            })
            ->leftJoin('contacts as student_contacts', 'students.contact_id', '=', 'student_contacts.id')
            ->leftJoin('admissions', 'students.admission_id', '=', 'admissions.id')
            ->leftJoin('batches', 'students.batch_id', '=', 'batches.id')
            ->leftJoin('courses', 'batches.course_id', '=', 'courses.id')
            ->leftJoin('employees', function ($join) {
                $join->on('form_submissions.model_id', '=', 'employees.id')
                    ->where('form_submissions.model_type', '=', 'Employee');
            })
            ->leftJoin('contacts as employee_contacts', 'employees.contact_id', '=', 'employee_contacts.id')
            ->where('form_submissions.uuid', $uuid)
            ->getOrFail(trans('form.submission.submission'));
    }

    public function isMediaAccessible(Form $form, Submission $submission)
    {
        if (auth()->user()->can('form-submission:manage')) {
            return true;
        }

        if ($submission->user_id == auth()->id()) {
            return true;
        }

        throw ValidationException::withMessages(['message' => trans('user.errors.permission_denied')]);
    }

    public function delete(Form $form, Submission $submission)
    {
        foreach ($submission->records as $submissionRecord) {
            foreach ($submissionRecord->getMeta('images') ?? [] as $image) {
                \Storage::disk('public')->delete($image);
            }
        }

        $submission->delete();
    }
}
