<?php

namespace App\Imports\Transport;

use App\Concerns\ItemImport;
use App\Models\Academic\Period;
use App\Models\Transport\Stoppage;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StoppageImport implements ToCollection, WithHeadingRow
{
    use ItemImport;

    protected $limit = 100;

    public function collection(Collection $rows)
    {
        $this->validateHeadings();

        if (count($rows) > $this->limit) {
            throw ValidationException::withMessages(['message' => trans('general.errors.max_import_limit_crossed', ['attribute' => $this->limit])]);
        }

        $logFile = $this->getLogFile('stoppage');

        $errors = $this->validate($rows);

        $this->checkForErrors('stoppage', $errors);

        if (! request()->boolean('validate') && ! \Storage::disk('local')->exists($logFile)) {
            $this->import($rows);
        }
    }

    private function import(Collection $rows)
    {
        $importBatchUuid = (string) Str::uuid();

        activity()->disableLogging();

        foreach ($rows as $index => $row) {
            Stoppage::forceCreate([
                'name' => Arr::get($row, 'name'),
                'description' => Arr::get($row, 'description'),
                'period_id' => auth()->user()->current_period_id,
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
        $imports['stoppage'] = Arr::get($meta, 'imports.stoppage', []);
        $imports['stoppage'][] = [
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
        $existingNames = Stoppage::byPeriod()->pluck('name')->all();

        $errors = [];

        $newNames = [];
        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;

            $name = Arr::get($row, 'name');
            $description = Arr::get($row, 'description');

            if (! $name) {
                $errors[] = $this->setError($rowNo, trans('transport.stoppage.props.name'), 'required');
            } elseif (strlen($name) < 2 || strlen($name) > 100) {
                $errors[] = $this->setError($rowNo, trans('transport.stoppage.props.name'), 'min_max', ['min' => 2, 'max' => 100]);
            } elseif (in_array($name, $existingNames)) {
                $errors[] = $this->setError($rowNo, trans('transport.stoppage.props.name'), 'exists');
            } elseif (in_array($name, $newNames)) {
                $errors[] = $this->setError($rowNo, trans('transport.stoppage.props.name'), 'duplicate');
            }

            if ($description && (strlen($description) < 2 || strlen($description) > 1000)) {
                $errors[] = $this->setError($rowNo, trans('transport.stoppage.props.description'), 'min_max', ['min' => 2, 'max' => 100]);
            }

            $newNames[] = $name;
        }

        return $errors;
    }
}
