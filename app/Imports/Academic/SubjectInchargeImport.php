<?php

namespace App\Imports\Academic;

use App\Concerns\ItemImport;
use App\Helpers\CalHelper;
use App\Domain\Academic\Models\Batch;
use App\Domain\Academic\Models\Course;
use App\Domain\Academic\Models\Period;
use App\Domain\Academic\Models\Subject;
use App\Domain\Academic\Models\SubjectRecord;
use App\Models\Employee\Employee;
use App\Models\Incharge;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class SubjectInchargeImport implements ToCollection, WithHeadingRow
{
    use ItemImport;

    protected $limit = 500;

    public function collection(Collection $rows)
    {
        $this->validateHeadings();

        if (count($rows) > $this->limit) {
            throw ValidationException::withMessages(['message' => trans('general.errors.max_import_limit_crossed', ['attribute' => $this->limit])]);
        }

        $logFile = $this->getLogFile('subject_incharge');

        [$errors, $rows] = $this->validate($rows);

        $this->checkForErrors('subject_incharge', $errors);

        if (! request()->boolean('validate') && ! \Storage::disk('local')->exists($logFile)) {
            $this->import($rows);
        }
    }

    private function import(Collection $rows)
    {
        $importBatchUuid = (string) Str::uuid();

        activity()->disableLogging();

        foreach ($rows as $row) {
            $startDate = Arr::get($row, 'date');

            if (is_int($startDate)) {
                $startDate = Date::excelToDateTimeObject($startDate)->format('Y-m-d');
            } else {
                $startDate = Carbon::parse($startDate)->toDateString();
            }

            Incharge::firstOrCreate([
                'model_type' => 'Subject',
                'model_id' => Arr::get($row, 'subject_id'),
                'detail_type' => Arr::get($row, 'batch_id') ? 'Batch' : null,
                'detail_id' => Arr::get($row, 'batch_id'),
                'employee_id' => Arr::get($row, 'employee_id'),
                'start_date' => $startDate,
                'meta' => [
                    'import_batch' => $importBatchUuid,
                    'is_imported' => true,
                ],
            ]);
        }

        $period = Period::query()
            ->whereId(auth()->user()->current_period_id)
            ->first();

        $meta = $period->meta ?? [];
        $imports['subject_incharge'] = Arr::get($meta, 'imports.subject_incharge', []);
        $imports['subject_incharge'][] = [
            'uuid' => $importBatchUuid,
            'total' => count($rows),
            'created_at' => now()->toDateTimeString(),
        ];

        $meta['imports'] = $imports;
        $period->meta = $meta;
        $period->save();

        activity()->enableLogging();
    }

    private function validate(Collection $rows)
    {
        $employees = Employee::query()
            ->summary()
            ->get();

        $courses = Course::query()
            ->byPeriod()
            ->get();

        $batches = Batch::query()
            ->byPeriod()
            ->get();

        $subjects = Subject::query()
            ->byPeriod()
            ->get();

        $subjectRecords = SubjectRecord::query()
            ->byPeriod()
            ->get();

        $errors = [];

        $newRows = [];
        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;

            $name = Arr::get($row, 'name');
            $courseName = Arr::get($row, 'course');
            $batchName = Arr::get($row, 'batch');
            $date = Arr::get($row, 'date');
            $subjectName = Arr::get($row, 'subject');

            if (is_int($date)) {
                $date = Date::excelToDateTimeObject($date)->format('Y-m-d');
            }

            if ($date && ! CalHelper::validateDate($date)) {
                $errors[] = $this->setError($rowNo, trans('employee.incharge.props.start_date'), 'invalid');
            }

            if (! $name) {
                $errors[] = $this->setError($rowNo, trans('employee.props.name'), 'required');
            } elseif (! $employees->filter(function ($item) use ($name) {
                return strtolower($item->name) == strtolower($name) || $item->code_number == $name;
            })->first()) {
                $errors[] = $this->setError($rowNo, trans('employee.props.name'), 'invalid');
            }

            // if (! $courseName) {
            //     $errors[] = $this->setError($rowNo, trans('academic.course.course'), 'required');
            // } elseif (! $courses->firstWhere('name', $courseName)) {
            //     $errors[] = $this->setError($rowNo, trans('academic.course.course'), 'invalid');
            // }

            if ($courseName && $batchName) {
                if (! $courses->firstWhere('name', $courseName)) {
                    $errors[] = $this->setError($rowNo, trans('academic.course.course'), 'invalid');
                }

                if (! $batches->firstWhere('name', $batchName)) {
                    $errors[] = $this->setError($rowNo, trans('academic.batch.batch'), 'invalid');
                }
            }

            // if (! $batchName) {
            //     $errors[] = $this->setError($rowNo, trans('academic.batch.batch'), 'required');
            // } elseif (! $batches->firstWhere('name', $batchName)) {
            //     $errors[] = $this->setError($rowNo, trans('academic.batch.batch'), 'invalid');
            // }

            $employee = $employees->filter(function ($item) use ($name) {
                return strtolower($item->name) == strtolower($name) || $item->code_number == $name;
            })->first();
            $subject = $subjects->firstWhere('name', $subjectName);
            $course = $courses->firstWhere('name', $courseName);
            $batch = $batches->where('course_id', $course?->id)
                ->where('name', $batchName)
                ->first();

            $subjectRecord = null;

            if (! $subjectName) {
                $errors[] = $this->setError($rowNo, trans('academic.subject.subject'), 'required');
            } elseif (! $subjects->firstWhere('name', $subjectName)) {
                $errors[] = $this->setError($rowNo, trans('academic.subject.subject'), 'invalid');
            } else {
                if ($courseName && $batchName) {
                    $subjectRecord = $subjectRecords->where('subject_id', $subject->id)
                        ->filter(function ($subjectRecord) use ($batch) {
                            return $subjectRecord->course_id == $batch?->course_id || $subjectRecord->batch_id == $batch?->id;
                        })
                        ->first();

                    if (! $subjectRecord) {
                        $errors[] = $this->setError($rowNo, trans('academic.subject.record'), 'invalid');
                    }
                }
            }

            $row['employee_id'] = $employee?->id;
            $row['batch_id'] = $batch?->id;
            $row['course_id'] = $course?->id;
            $row['subject_id'] = $subject?->id;
            $row['subject_record_id'] = $subjectRecord?->id;

            $newRows[] = $row;
        }

        $rows = collect($newRows);

        return [$errors, $rows];
    }
}
