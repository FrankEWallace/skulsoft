<?php

namespace App\Services\Student;

use App\Actions\Student\FetchBatchWiseStudent;
use App\Http\Resources\Student\StudentResource;
use App\Models\Academic\Batch;
use App\Models\Academic\Subject;
use App\Models\Student\SubjectWiseStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class SubjectService
{
    public function preRequisite(Request $request)
    {
        return [];
    }

    private function validateInput(Request $request): array
    {
        $batch = Batch::query()
            ->byPeriod()
            ->filterAccessible()
            ->whereUuid($request->batch)
            ->getOrFail(trans('academic.batch.batch'), 'batch');

        $subject = Subject::query()
            ->findByBatchOrFail($batch->id, $batch->course_id, $request->subject);

        if (! $subject->is_elective) {
            throw ValidationException::withMessages(['message' => trans('academic.subject.is_not_an_elective_subject')]);
        }

        return [
            'batch' => $batch,
            'subject' => $subject,
        ];
    }

    public function fetch(Request $request)
    {
        $data = $this->validateInput($request);

        $batch = $data['batch'];
        $subject = $data['subject'];

        $request->merge([
            'select_all' => true,
            'include_elective_subject' => true,
        ]);

        $students = (new FetchBatchWiseStudent)->execute($request->all());

        $subjectWiseStudents = SubjectWiseStudent::whereSubjectId($subject->id)
            ->get();

        foreach ($students as $student) {
            $subjectWiseStudent = $subjectWiseStudents->where('student_id', $student->id)->first();

            $student->has_elective_subject = $subjectWiseStudent ? true : false;
        }

        return StudentResource::collection($students);
    }

    public function store(Request $request)
    {
        $data = $this->validateInput($request);

        $batch = $data['batch'];
        $subject = $data['subject'];

        $request->merge(['select_all' => true]);

        $students = (new FetchBatchWiseStudent)->execute($request->all(), true);

        if (array_diff(Arr::pluck($request->students, 'uuid'), Arr::pluck($students, 'uuid'))) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
        }

        $optedOutStudents = [];
        foreach ($request->students as $input) {
            $isElective = Arr::get($input, 'has_elective_subject');

            $student = collect($students)->where('uuid', Arr::get($input, 'uuid'))->first();

            if (! $isElective) {
                $optedOutStudents[] = Arr::get($student, 'id');

                continue;
            }

            SubjectWiseStudent::firstOrCreate([
                'batch_id' => $batch->id,
                'subject_id' => $subject->id,
                'student_id' => Arr::get($student, 'id'),
            ]);
        }

        SubjectWiseStudent::whereSubjectId($subject->id)
            ->whereIn('student_id', $optedOutStudents)
            ->delete();
    }
}
