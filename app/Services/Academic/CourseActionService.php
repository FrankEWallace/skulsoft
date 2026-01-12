<?php

namespace App\Services\Academic;

use App\Enums\OptionType;
use App\Domain\Academic\Models\Batch;
use App\Domain\Academic\Models\Course;
use App\Domain\Academic\Models\EnrollmentSeat;
use App\Domain\Academic\Models\Period;
use App\Models\Guardian;
use App\Models\Option;
use App\Models\Student\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class CourseActionService
{
    public function updateConfig(Request $request, Course $course): void
    {
        //
    }

    public function addBatches(Request $request, Course $course): void
    {
        $batches = collect($request->batches ?? []);

        $existingBatches = Batch::query()
            ->whereCourseId($course->id)
            ->whereIn('name', $batches->pluck('name')->all())
            ->exists();

        if ($existingBatches) {
            throw ValidationException::withMessages([
                'message' => trans('global.duplicate', ['attribute' => trans('academic.batch.batch')]),
            ]);
        }

        foreach ($batches as $batch) {
            Batch::forceCreate([
                'course_id' => $course->id,
                'name' => Arr::get($batch, 'name'),
                'max_strength' => ! empty(Arr::get($batch, 'max_strength')) ? Arr::get($batch, 'max_strength') : null,
                'config' => [
                    'roll_number_prefix' => Arr::get($batch, 'roll_number_prefix'),
                ],
            ]);
        }
    }

    public function reorder(Request $request): void
    {
        $courses = $request->courses ?? [];

        $allCourses = Course::query()
            ->byPeriod()
            ->get();

        foreach ($courses as $index => $courseItem) {
            $course = $allCourses->firstWhere('uuid', Arr::get($courseItem, 'uuid'));

            if (! $course) {
                continue;
            }

            $course->position = $index + 1;
            $course->save();
        }
    }

    public function reorderBatch(Request $request): void
    {
        $course = Course::query()
            ->byPeriod()
            ->where('uuid', $request->course)
            ->firstOrFail();

        $batches = Batch::query()
            ->whereCourseId($course->id)
            ->get();

        foreach ($request->batches as $index => $batchItem) {
            $batch = $batches->firstWhere('uuid', Arr::get($batchItem, 'uuid'));

            if (! $batch) {
                continue;
            }

            $batch->position = $index + 1;
            $batch->save();
        }
    }

    public function updateCurrentPeriod(Request $request, Course $course): void
    {
        $period = Period::query()
            ->byTeam()
            ->whereId($request->period_id)
            ->first();

        if (! $period) {
            throw ValidationException::withMessages([
                'message' => trans('global.could_not_find', ['attribute' => trans('academic.period.period')]),
            ]);
        }

        $students = Student::query()
            ->select('students.id', 'contacts.id as contact_id', 'users.id as user_id')
            ->join('contacts', 'students.contact_id', '=', 'contacts.id')
            ->join('users', 'contacts.user_id', '=', 'users.id')
            ->join('batches', 'students.batch_id', '=', 'batches.id')
            ->where('batches.course_id', $course->id)
            ->get();

        $guardians = Guardian::query()
            ->select('guardians.id', 'contacts.id as contact_id', 'users.id as user_id')
            ->join('contacts', 'guardians.contact_id', '=', 'contacts.id')
            ->join('users', 'contacts.user_id', '=', 'users.id')
            ->whereIn('primary_contact_id', $students->pluck('contact_id')->all())
            ->get();

        $userIds = array_merge(
            $students->pluck('user_id')->all(),
            $guardians->pluck('user_id')->all()
        );

        $userIds = array_unique($userIds);

        $users = User::query()
            ->whereIn('id', $userIds)
            ->chunk(20, function ($users) use ($request) {
                $users->each(function (User $user) use ($request) {
                    $preference = $user->preference;
                    $preference['academic']['period_id'] = $request->period_id;
                    $user->preference = $preference;
                    $user->save();
                });
            });

        $meta = $course->meta;
        $meta['period_history'][] = [
            'name' => $period->name,
            'datetime' => now()->toDateTimeString(),
        ];

        $course->meta = $meta;
        $course->save();
    }

    public function updateEnrollmentSeat(Request $request, Course $course): void
    {
        $enrollmentTypes = Option::query()
            ->byTeam()
            ->where('type', OptionType::STUDENT_ENROLLMENT_TYPE)
            ->get();

        $inputEnrollmentTypes = collect($request->enrollment_types ?? []);

        foreach ($enrollmentTypes as $enrollmentType) {
            $inputEnrollmentType = $inputEnrollmentTypes->firstWhere('uuid', $enrollmentType->uuid);

            $seat = Arr::get($inputEnrollmentType, 'max_seat', 0);

            $enrollmentSeat = EnrollmentSeat::firstOrCreate([
                'course_id' => $course->id,
                'enrollment_type_id' => $enrollmentType->id,
            ]);

            $enrollmentSeat->max_seat = $seat;
            $enrollmentSeat->save();
        }
    }
}
