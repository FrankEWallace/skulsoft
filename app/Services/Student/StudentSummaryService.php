<?php

namespace App\Services\Student;

use App\Helpers\CalHelper;
use App\Http\Resources\Student\StudentSummaryResource;
use App\Models\Student\Student;
use Illuminate\Http\Request;

class StudentSummaryService
{
    public function summary(Request $request)
    {
        $student = Student::query()
            ->auth()
            ->firstOrFail();

        $studentSummary = Student::findSummaryByUuidOrFail($student->uuid);

        $data['student'] = StudentSummaryResource::make($studentSummary);

        if ($request->has('fee_summary')) {
            if ($request->has('date')) {
                $date = $request->query('date');

                if (! CalHelper::validateDate($date)) {
                    $date = today()->toDateString();
                }

                $data['fee_summary'] = $student->getFeeSummaryOnDate($date);
            } else {
                $data['fee_summary'] = $student->getFeeSummary();
            }
        }

        if ($request->has('subject_summary')) {
            $data['subject_summary'] = $student->getSubjectSummary();
        }

        if ($request->has('attendance_summary')) {
            $data['attendance_summary'] = $student->getAttendanceSummary();
        }

        return $data;
    }
}
