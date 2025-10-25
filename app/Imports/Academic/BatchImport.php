<?php

namespace App\Imports\Academic;

use App\Concerns\ItemImport;
use App\Models\Academic\Batch;
use App\Models\Academic\Course;
use App\Models\Academic\Period;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BatchImport implements ToCollection, WithHeadingRow
{
    use ItemImport;

    protected $limit = 100;

    public function collection(Collection $rows)
    {
        $this->validateHeadings();

        if (count($rows) > $this->limit) {
            throw ValidationException::withMessages(['message' => trans('general.errors.max_import_limit_crossed', ['attribute' => $this->limit])]);
        }

        $logFile = $this->getLogFile('batch');

        $errors = $this->validate($rows);

        $this->checkForErrors('batch', $errors);

        if (! request()->boolean('validate') && ! \Storage::disk('local')->exists($logFile)) {
            $this->import($rows);
        }
    }

    private function import(Collection $rows)
    {
        $importBatchUuid = (string) Str::uuid();

        activity()->disableLogging();

        $courses = Course::query()
            ->byPeriod()
            ->get();

        foreach ($rows as $index => $row) {
            $courseId = $courses->firstWhere('name', Arr::get($row, 'course'))?->id;

            $maxStrength = Arr::get($row, 'max_strength');

            Batch::forceCreate([
                'course_id' => $courseId,
                'name' => Arr::get($row, 'name'),
                'max_strength' => ! empty($maxStrength) ? $maxStrength : null,
                'position' => $index + 1,
                'description' => Arr::get($row, 'description'),
                'meta' => [
                    'roll_number_prefix' => Arr::get($row, 'roll_number_prefix'),
                    'import_batch' => $importBatchUuid,
                    'is_imported' => true,
                ],
            ]);
        }

        $period = Period::query()
            ->whereId(auth()->user()->current_period_id)
            ->first();

        $meta = $period->meta ?? [];
        $imports['batch'] = Arr::get($meta, 'imports.batch', []);
        $imports['batch'][] = [
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
        $courses = Course::query()
            ->byPeriod()
            ->get()
            ->pluck('name')
            ->all();

        $existingNames = Batch::query()
            ->select('batches.name', 'courses.name as course_name')
            ->join('courses', 'batches.course_id', '=', 'courses.id')
            ->join('divisions', 'courses.division_id', '=', 'divisions.id')
            ->where('divisions.period_id', auth()->user()->current_period_id)
            ->get()
            ->map(function ($batch) {
                return $batch->course_name.' - '.$batch->name;
            })
            ->all();

        $errors = [];

        $newNames = [];
        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;

            $name = Arr::get($row, 'name');
            $course = Arr::get($row, 'course');
            $maxStrength = Arr::get($row, 'max_strength');
            $rollNumberPrefix = Arr::get($row, 'roll_number_prefix');
            $description = Arr::get($row, 'description');

            $uniqueName = $course.' - '.$name;

            if (! $name) {
                $errors[] = $this->setError($rowNo, trans('academic.batch.props.name'), 'required');
            } elseif (strlen($name) < 1 || strlen($name) > 100) {
                $errors[] = $this->setError($rowNo, trans('academic.batch.props.name'), 'min_max', ['min' => 1, 'max' => 100]);
            } elseif (in_array($uniqueName, $existingNames)) {
                $errors[] = $this->setError($rowNo, trans('academic.batch.props.name'), 'exists');
            } elseif (in_array($uniqueName, $newNames)) {
                $errors[] = $this->setError($rowNo, trans('academic.batch.props.name'), 'duplicate');
            }

            if ($course) {
                if (! in_array($course, $courses)) {
                    $errors[] = $this->setError($rowNo, trans('academic.course.course'), 'invalid');
                }
            }

            if ($maxStrength) {
                if (! is_numeric($maxStrength)) {
                    $errors[] = $this->setError($rowNo, trans('academic.batch.props.max_strength'), 'numeric');
                } elseif ($maxStrength < 0) {
                    $errors[] = $this->setError($rowNo, trans('academic.batch.props.max_strength'), 'min', ['min' => 0]);
                }
            }

            if ($description && (strlen($description) < 2 || strlen($description) > 1000)) {
                $errors[] = $this->setError($rowNo, trans('academic.batch.props.description'), 'min_max', ['min' => 2, 'max' => 1000]);
            }

            $newNames[] = $uniqueName;
        }

        return $errors;
    }
}
