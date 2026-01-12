<?php

namespace App\Services\Student;

use App\Domain\Academic\Models\Period;
use App\Models\Student\Registration;
use App\Models\Student\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class StudentImportHistoryService
{
    public function fetch(Request $request)
    {
        $period = Period::query()
            ->find(auth()->user()->current_period_id);

        return collect($period->getMeta('imports')['student'] ?? [])->map(function ($import) {
            return [
                'uuid' => $import['uuid'] ?? null,
                'count' => $import['total'] ?? 0,
                'imported_at' => \Cal::dateTime($import['created_at'] ?? null),
            ];
        });
    }

    public function delete(Request $request, string $uuid)
    {
        $period = Period::query()
            ->find(auth()->user()->current_period_id);

        $importRecord = collect($period->getMeta('imports')['student'] ?? [])->firstWhere('uuid', $uuid);

        if (! $importRecord) {
            throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('student.student')])]);
        }

        $students = Student::query()
            ->with('contact')
            ->where('meta->is_imported', true)
            ->where('meta->import_batch', $uuid)
            ->get();

        foreach ($students as $student) {
            $records = Student::query()
                ->where('students.id', '!=', $student->id)
                ->where('students.contact_id', $student->contact_id)
                ->where('students.admission_id', $student->admission_id)
                ->join('batches', 'batches.id', '=', 'students.batch_id')
                ->join('courses', 'courses.id', '=', 'batches.course_id')
                ->join('periods', 'periods.id', '=', 'students.period_id')
                ->select('students.uuid', 'courses.name as course_name', 'periods.name as period_name', 'batches.name as batch_name')
                ->get();

            if ($records->count() > 0) {
                throw ValidationException::withMessages(['message' => trans('student.could_not_delete_with_multiple_records')]);
            }

            $feeSummary = $student->getFeeSummary();

            if (Arr::get($feeSummary, 'paid_fee')?->value > 0) {
                throw ValidationException::withMessages(['message' => trans('student.could_not_delete_with_paid_fee')]);
            }
        }

        \DB::beginTransaction();

        foreach ($students as $student) {
            Registration::query()
                ->where('contact_id', $student->contact_id)
                ->where('period_id', $student->period_id)
                ->delete();

            User::query()
                ->whereId($student->contact?->user_id)
                ->delete();
        }

        Student::query()
            ->where('meta->is_imported', true)
            ->where('meta->import_batch', $uuid)
            ->delete();

        \DB::commit();

        $importRecord = collect($period->getMeta('imports')['student'] ?? [])->reject(function ($record) use ($uuid) {
            return $record['uuid'] === $uuid;
        })->all();

        $imports = $period->getMeta('imports', []);
        $imports['student'] = $importRecord;

        $period->setMeta(['imports' => $imports]);
        $period->save();
    }
}
