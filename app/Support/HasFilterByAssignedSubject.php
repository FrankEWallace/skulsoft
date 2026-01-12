<?php

namespace App\Support;

use App\Domain\Academic\Models\Subject;
use App\Models\Employee\Employee;
use App\Models\Incharge;
use App\Models\Student\Student;
use App\Models\Student\SubjectWiseStudent;

trait HasFilterByAssignedSubject
{
    public function getFilteredSubjects(): array
    {
        $filterSubjectUuids = [];

        if (! config('config.resource.enable_filter_by_assigned_subject')) {
            return $filterSubjectUuids;
        }

        if (! auth()->user()->hasAnyRole(['admin', 'student', 'guardian'])) {
            $employeeId = Employee::query()
                ->auth()
                ->first()
                ?->id;

            $subjectIncharges = Incharge::query()
                ->whereEmployeeId($employeeId)
                ->whereModelType('Subject')
                ->pluck('model_id')
                ->all();

            $filterSubjectUuids = Subject::query()
                ->whereIn('id', $subjectIncharges)
                ->get()
                ->pluck('uuid')
                ->all();
        } elseif (auth()->user()->hasRole('student')) {
            $student = Student::query()
                ->select('students.id', 'batches.id as batch_id', 'batches.course_id')
                ->join('contacts', 'students.contact_id', '=', 'contacts.id')
                ->join('batches', 'students.batch_id', '=', 'batches.id')
                ->where('contacts.user_id', auth()->id())
                ->where('contacts.team_id', auth()->user()->current_team_id)
                ->first();

            $electiveSubjects = SubjectWiseStudent::query()
                ->where('student_id', $student->id)
                ->get();

            $allSubjects = Subject::query()
                ->withSubjectRecord($student->batch_id, $student->course_id)
                ->get();

            $filterSubjectUuids = $allSubjects->filter(function ($subject) use ($electiveSubjects) {
                return ! $subject->is_elective || ($subject->is_elective && $electiveSubjects->pluck('subject_id')->contains($subject->id));
            })->pluck('uuid')->all();
        }

        return $filterSubjectUuids;
    }
}
