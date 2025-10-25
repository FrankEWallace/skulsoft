<?php

namespace App\Services\Resource;

use App\Http\Resources\Resource\AssignmentSubmissionResource;
use App\Models\Employee\Employee;
use App\Models\Resource\Assignment;
use App\Models\Resource\AssignmentSubmission;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AssignmentSubmissionService
{
    public function preRequisite(Request $request): array
    {
        return [];
    }

    public function getSubmissions(Request $request, Assignment $assignment): array
    {
        $students = collect([]);
        $pendingStudents = collect([]);
        $submissions = collect([]);

        if ($assignment->enable_marking == false) {
            return compact('students', 'pendingStudents');
        }

        if (auth()->user()->hasRole('student')) {
            $student = Student::query()
                ->auth()
                ->first();

            $students = Student::query()
                ->summary()
                ->where('students.id', $student->id)
                ->get();

            $submissions = AssignmentSubmission::query()
                ->with('media')
                ->where('assignment_id', $assignment->id)
                ->where('student_id', $student->id)
                ->get();
        }

        if (! auth()->user()->hasAnyRole(['student', 'guardian'])) {
            $studentIds = AssignmentSubmission::query()
                ->whereAssignmentId($assignment->id)
                ->pluck('student_id')
                ->all();

            $pendingStudents = Student::query()
                ->summary()
                ->whereIn('students.batch_id', $assignment->records->pluck('batch_id'))
                ->whereNotIn('students.id', $studentIds)
                ->get();

            $students = Student::query()
                ->summary()
                ->whereIn('students.id', $studentIds)
                ->get();

            $submissions = AssignmentSubmission::query()
                ->with(['media'])
                ->where('assignment_id', $assignment->id)
                ->get();
        }

        $pendingStudents = $pendingStudents->map(function ($student) {
            return [
                'uuid' => $student->uuid,
                'name' => $student->name,
                'course_name' => $student->course_name,
                'batch_name' => $student->batch_name,
                'roll_number' => $student->roll_number,
                'code_number' => $student->code_number,
                'photo' => $student->photo_url,
                'photo_url' => url($student->photo_url),
            ];
        });

        $students = $students->map(function ($student) use ($submissions) {
            $submissions = $submissions->where('student_id', $student->id);

            return [
                'uuid' => $student->uuid,
                'name' => $student->name,
                'course_name' => $student->course_name,
                'batch_name' => $student->batch_name,
                'roll_number' => $student->roll_number,
                'code_number' => $student->code_number,
                'photo' => $student->photo_url,
                'photo_url' => url($student->photo_url),
                'obtained_mark' => $submissions?->first()?->obtained_mark,
                'comment' => $submissions?->first()?->comment,
                'submissions' => $submissions ? AssignmentSubmissionResource::collection($submissions) : [],
            ];
        });

        $employee = Employee::query()
            ->auth()
            ->first();

        $canEvaluate = $assignment->employee_id == $employee?->id ? true : false;

        return compact('students', 'pendingStudents', 'canEvaluate');
    }

    public function submit(Request $request, Assignment $assignment): void
    {
        if ($assignment->due_date->carbon()->isPast()) {
            throw ValidationException::withMessages(['message' => trans('resource.assignment.submission_closed')]);
        }

        if ($assignment->has_submitted) {
            throw ValidationException::withMessages(['message' => trans('resource.assignment.already_submitted')]);
        }

        \DB::beginTransaction();

        $submission = AssignmentSubmission::forceCreate($this->formatParams($request, $assignment));

        $submission->addMedia($request);

        \DB::commit();
    }

    private function formatParams(Request $request, Assignment $assignment): array
    {
        $formatted = [
            'assignment_id' => $assignment->id,
            'student_id' => $request->student_id,
            'description' => $request->description,
            'submitted_at' => now()->toDateTimeString(),
        ];

        return $formatted;
    }
}
