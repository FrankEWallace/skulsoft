<?php

namespace App\Actions\Academic;

use App\Domain\Academic\Models\Course;
use App\Domain\Academic\Models\EnrollmentSeat;
use App\Models\Student\Student;

class UpdateEnrollmentSeat
{
    public function execute(Course $course, array $params = [])
    {
        $enrollmentSeats = EnrollmentSeat::query()
            ->where('course_id', $course->id)
            ->get();

        if ($enrollmentSeats->isEmpty()) {
            return;
        }

        $students = Student::query()
            ->select(\DB::raw('COUNT(students.id) as total'), 'enrollment_type_id')
            ->join('batches', 'batches.id', '=', 'students.batch_id')
            ->join('admissions', 'admissions.id', '=', 'students.admission_id')
            ->where('batches.course_id', $course->id)
            ->where(function ($q) {
                $q->whereNull('students.cancelled_at')->where(function ($q) {
                    $q->whereNull('admissions.leaving_date')
                        ->orWhere('admissions.leaving_date', '>', today()->toDateString());
                });
            })
            ->groupBy('enrollment_type_id')
            ->get();

        foreach ($students as $student) {
            $enrollmentSeat = $enrollmentSeats->firstWhere('enrollment_type_id', $student->enrollment_type_id);

            if (! $enrollmentSeat) {
                continue;
            }

            $enrollmentSeat->booked_seat = $student->total;
            $enrollmentSeat->save();
        }
    }
}
