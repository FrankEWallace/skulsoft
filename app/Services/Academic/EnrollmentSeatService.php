<?php

namespace App\Services\Academic;

use App\Enums\OptionType;
use App\Http\Resources\Academic\CourseResource;
use App\Http\Resources\OptionResource;
use App\Domain\Academic\Models\Course;
use App\Domain\Academic\Models\EnrollmentSeat;
use App\Models\Option;
use Illuminate\Http\Request;

class EnrollmentSeatService
{
    public function preRequisite(Request $request)
    {
        $enrollmentTypes = OptionResource::collection(Option::query()
            ->byTeam()
            ->where('type', OptionType::STUDENT_ENROLLMENT_TYPE)
            ->get());

        $courses = CourseResource::collection(Course::query()
            ->byPeriod()
            ->filterAccessible()
            ->get());

        return compact('enrollmentTypes', 'courses');
    }

    public function create(Request $request): EnrollmentSeat
    {
        \DB::beginTransaction();

        $enrollmentSeat = EnrollmentSeat::forceCreate($this->formatParams($request));

        \DB::commit();

        return $enrollmentSeat;
    }

    private function formatParams(Request $request, ?EnrollmentSeat $enrollmentSeat = null): array
    {
        $formatted = [
            'course_id' => $request->course_id,
            'enrollment_type_id' => $request->enrollment_type_id,
            'max_seat' => $request->max_seat,
            'description' => $request->description,
        ];

        if (! $enrollmentSeat) {
            $formatted['position'] = $request->integer('position', 0);
        }

        return $formatted;
    }

    public function update(Request $request, EnrollmentSeat $enrollmentSeat): void
    {
        \DB::beginTransaction();

        $enrollmentSeat->forceFill($this->formatParams($request, $enrollmentSeat))->save();

        \DB::commit();
    }

    public function deletable(EnrollmentSeat $enrollmentSeat): bool
    {
        return true;
    }
}
