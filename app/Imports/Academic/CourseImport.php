<?php

namespace App\Imports\Academic;

use App\Concerns\ItemImport;
use App\Models\Academic\Course;
use App\Models\Academic\Division;
use App\Models\Academic\Period;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CourseImport implements ToCollection, WithHeadingRow
{
    use ItemImport;

    protected $limit = 100;

    public function collection(Collection $rows)
    {
        $this->validateHeadings();

        if (count($rows) > $this->limit) {
            throw ValidationException::withMessages(['message' => trans('general.errors.max_import_limit_crossed', ['attribute' => $this->limit])]);
        }

        $logFile = $this->getLogFile('course');

        $errors = $this->validate($rows);

        $this->checkForErrors('course', $errors);

        if (! request()->boolean('validate') && ! \Storage::disk('local')->exists($logFile)) {
            $this->import($rows);
        }
    }

    private function import(Collection $rows)
    {
        $importBatchUuid = (string) Str::uuid();

        activity()->disableLogging();

        $divisions = Division::query()
            ->byPeriod()
            ->get();

        foreach ($rows as $index => $row) {
            $enableRegistration = Arr::get($row, 'enable_registration');

            $enableRegistration = in_array(strtolower($enableRegistration), ['yes', 'on', 'true', '1']) ? true : false;

            $registrationFee = $enableRegistration ? (float) Arr::get($row, 'registration_fee') : 0;

            $divisionId = $divisions->firstWhere('name', Arr::get($row, 'division'))?->id;

            Course::forceCreate([
                'division_id' => $divisionId,
                'name' => Arr::get($row, 'name'),
                'term' => Arr::get($row, 'term'),
                'code' => Arr::get($row, 'code'),
                'shortcode' => Arr::get($row, 'shortcode'),
                'enable_registration' => $enableRegistration,
                'registration_fee' => $registrationFee,
                'position' => $index + 1,
                'description' => Arr::get($row, 'description'),
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
        $imports['course'] = Arr::get($meta, 'imports.course', []);
        $imports['course'][] = [
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
        $divisions = Division::query()
            ->byPeriod()
            ->get()
            ->pluck('name')
            ->all();

        $existingNames = Course::byPeriod()->pluck('name')->all();
        $existingCodes = Course::byPeriod()->pluck('code')->all();
        $existingShortcodes = Course::byPeriod()->pluck('shortcode')->all();

        $errors = [];

        $newNames = [];
        $newCodes = [];
        $newShortcodes = [];
        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;

            $name = Arr::get($row, 'name');
            $division = Arr::get($row, 'division');
            $term = Arr::get($row, 'term');
            $code = Arr::get($row, 'code');
            $shortcode = Arr::get($row, 'shortcode');
            $enableRegistration = Arr::get($row, 'enable_registration');

            $enableRegistration = in_array(strtolower($enableRegistration), ['yes', 'on', 'true', '1']) ? true : false;

            $registrationFee = $enableRegistration ? (float) Arr::get($row, 'registration_fee') : 0;
            $description = Arr::get($row, 'description');

            if (! $name) {
                $errors[] = $this->setError($rowNo, trans('academic.course.props.name'), 'required');
            } elseif (strlen($name) < 2 || strlen($name) > 100) {
                $errors[] = $this->setError($rowNo, trans('academic.course.props.name'), 'min_max', ['min' => 2, 'max' => 100]);
            } elseif (in_array($name, $existingNames)) {
                $errors[] = $this->setError($rowNo, trans('academic.course.props.name'), 'exists');
            } elseif (in_array($name, $newNames)) {
                $errors[] = $this->setError($rowNo, trans('academic.course.props.name'), 'duplicate');
            }

            if ($code) {
                if (strlen($code) < 2 || strlen($code) > 100) {
                    $errors[] = $this->setError($rowNo, trans('academic.course.props.code'), 'min_max', ['min' => 2, 'max' => 100]);
                } elseif (in_array($code, $existingCodes)) {
                    $errors[] = $this->setError($rowNo, trans('academic.course.props.code'), 'exists');
                } elseif (in_array($code, $newCodes)) {
                    $errors[] = $this->setError($rowNo, trans('academic.course.props.code'), 'duplicate');
                }
            }

            if ($shortcode) {
                if (strlen($shortcode) < 2 || strlen($shortcode) > 100) {
                    $errors[] = $this->setError($rowNo, trans('academic.course.props.shortcode'), 'min_max', ['min' => 2, 'max' => 100]);
                } elseif (in_array($shortcode, $existingShortcodes)) {
                    $errors[] = $this->setError($rowNo, trans('academic.course.props.code'), 'exists');
                } elseif (in_array($code, $newCodes)) {
                    $errors[] = $this->setError($rowNo, trans('academic.course.props.code'), 'duplicate');
                }
            }

            if ($division) {
                if (! in_array($division, $divisions)) {
                    $errors[] = $this->setError($rowNo, trans('academic.division.division'), 'invalid');
                }
            }

            if ($enableRegistration && $registrationFee) {
                if (! is_numeric($registrationFee)) {
                    $errors[] = $this->setError($rowNo, trans('academic.course.props.registration_fee'), 'numeric');
                } elseif ($registrationFee < 0) {
                    $errors[] = $this->setError($rowNo, trans('academic.course.props.registration_fee'), 'min', ['min' => 0]);
                }
            }

            if ($description && (strlen($description) < 2 || strlen($description) > 1000)) {
                $errors[] = $this->setError($rowNo, trans('academic.course.props.description'), 'min_max', ['min' => 2, 'max' => 100]);
            }

            $newNames[] = $name;
            $newCodes[] = $code;
            $newShortcodes[] = $shortcode;
        }

        return $errors;
    }
}
