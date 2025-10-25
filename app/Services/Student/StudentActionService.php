<?php

namespace App\Services\Student;

use App\Actions\CreateTag;
use App\Enums\OptionType;
use App\Models\Employee\Employee;
use App\Models\GroupMember;
use App\Models\Option;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class StudentActionService
{
    public function setDefaultPeriod(Request $request, Student $student)
    {
        $contact = $student->contact;

        $user = $contact->user;

        if (! $user) {
            throw ValidationException::withMessages([
                'message' => trans('global.could_not_find', ['attribute' => trans('user.user')]),
            ]);
        }

        $preference = $user->user_preference;
        $preference['academic'] = Arr::get($preference, 'academic', []);
        $preference['academic']['period_id'] = $student->period_id;

        $user->preference = $preference;
        $user->save();
    }

    public function updateTags(Request $request, Student $student)
    {
        $request->validate([
            'tags' => 'array',
            'tags.*' => 'required|string|distinct',
        ]);

        $tags = (new CreateTag)->execute($request->input('tags', []));

        $student->tags()->sync($tags);
    }

    private function getBulkStudents(Request $request): array
    {
        $selectAll = $request->boolean('select_all');

        $students = [];

        if ($selectAll) {
            $students = (new StudentListService)->listAll($request)->toArray();
        } else {
            $students = Student::query()
                ->whereIn('uuid', $request->students)
                ->filterAccessible(false)
                ->get()
                ->toArray();

            if (array_diff($request->students, Arr::pluck($students, 'uuid'))) {
                throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
            }
        }

        $uniqueStudents = array_unique(Arr::pluck($students, 'uuid'));

        if (count($uniqueStudents) !== count($students)) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
        }

        return $students;
    }

    public function updateBulkTags(Request $request)
    {
        $request->validate([
            'action' => 'required|string|in:assign,remove',
            'students' => 'array',
            'tags' => 'array',
            'tags.*' => 'required|string|distinct',
        ]);

        $students = $this->getBulkStudents($request);

        $tags = (new CreateTag)->execute($request->input('tags', []));

        if ($request->input('action') === 'assign') {
            foreach ($students as $student) {
                Student::query()
                    ->whereUuid(Arr::get($student, 'uuid'))
                    ->first()
                    ->tags()
                    ->sync($tags);
            }
        } else {
            foreach ($students as $student) {
                Student::query()
                    ->whereUuid(Arr::get($student, 'uuid'))
                    ->first()
                    ->tags()
                    ->detach($tags);
            }
        }
    }

    public function updateBulkGroups(Request $request)
    {
        $request->validate([
            'action' => 'required|string|in:assign,remove',
            'students' => 'array',
            'groups' => 'array',
            'groups.*' => 'required|uuid|distinct',
        ]);

        $studentGroup = Option::query()
            ->byTeam()
            ->where('type', OptionType::STUDENT_GROUP)
            ->whereIn('uuid', $request->input('groups', []))
            ->get();

        if (! $studentGroup->count()) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
        }

        $students = $this->getBulkStudents($request);

        if ($request->input('action') === 'assign') {
            foreach ($students as $student) {
                foreach ($studentGroup as $group) {
                    GroupMember::firstOrCreate([
                        'model_type' => 'Student',
                        'model_id' => Arr::get($student, 'id'),
                        'model_group_id' => $group->id,
                    ]);
                }
            }
        } else {
            foreach ($students as $student) {
                GroupMember::where('model_type', 'Student')
                    ->where('model_id', Arr::get($student, 'id'))
                    ->whereIn('model_group_id', $studentGroup->pluck('id'))
                    ->delete();
            }
        }
    }

    public function updateBulkMentor(Request $request)
    {
        $request->validate([
            'students' => 'array',
            'mentor' => 'required|uuid',
        ]);

        $mentor = Employee::query()
            ->summary()
            ->filterAccessible()
            ->where('employees.uuid', $request->mentor)
            ->getOrFail(trans('employee.employee'), 'employee');

        if (! $mentor) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
        }

        $students = $this->getBulkStudents($request);

        $students = Student::query()
            ->whereIn('uuid', Arr::pluck($students, 'uuid'))
            ->get();

        foreach ($students as $student) {
            $student->mentor_id = $mentor->id;
            $student->save();
        }
    }

    public function updateBulkEnrollmentType(Request $request)
    {
        $request->validate([
            'students' => 'array',
            'enrollment_type' => 'required|uuid',
        ]);

        $enrollmentType = Option::query()
            ->byTeam()
            ->where('type', OptionType::STUDENT_ENROLLMENT_TYPE)
            ->whereUuid($request->input('enrollment_type'))
            ->first();

        if (! $enrollmentType) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
        }

        $students = $this->getBulkStudents($request);

        $students = Student::query()
            ->whereIn('uuid', Arr::pluck($students, 'uuid'))
            ->get();

        foreach ($students as $student) {
            $student->enrollment_type_id = $enrollmentType->id;
            $student->save();
        }
    }

    public function updateBulkEnrollmentStatus(Request $request)
    {
        $request->validate([
            'students' => 'array',
            'enrollment_status' => 'required|uuid',
        ]);

        $enrollmentStatus = Option::query()
            ->byTeam()
            ->where('type', OptionType::STUDENT_ENROLLMENT_STATUS)
            ->whereUuid($request->input('enrollment_status'))
            ->first();

        if (! $enrollmentStatus) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
        }

        $students = $this->getBulkStudents($request);

        $students = Student::query()
            ->whereIn('uuid', Arr::pluck($students, 'uuid'))
            ->get();

        foreach ($students as $student) {
            if ($student->enrollment_status_id != $enrollmentStatus->id) {
                $enrollmentStatusLogs = $student->getMeta('enrollment_status_logs', []);

                $enrollmentStatusLogs[] = [
                    'enrollment_status' => $enrollmentStatus->name,
                    'created_at' => now()->toDateTimeString(),
                    'created_by' => auth()->user()->name,
                ];

                $student->setMeta([
                    'enrollment_status_logs' => $enrollmentStatusLogs,
                ]);
            }

            $student->enrollment_status_id = $enrollmentStatus->id;
            $student->save();
        }
    }
}
