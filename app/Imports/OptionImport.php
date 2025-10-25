<?php

namespace App\Imports;

use App\Concerns\ItemImport;
use App\Helpers\ListHelper;
use App\Models\Option;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class OptionImport implements ToCollection, WithHeadingRow
{
    use ItemImport;

    protected $limit = 10;

    public function collection(Collection $rows)
    {
        $this->validateHeadings();

        if (count($rows) > $this->limit) {
            throw ValidationException::withMessages(['message' => trans('general.errors.max_import_limit_crossed', ['attribute' => $this->limit])]);
        }

        $logFile = $this->getLogFile('option');

        $errors = $this->validate($rows);

        $this->checkForErrors('option', $errors);

        if (! request()->boolean('validate') && ! \Storage::disk('local')->exists($logFile)) {
            $this->import($rows);
        }
    }

    private function import(Collection $rows)
    {
        activity()->disableLogging();

        $colors = ListHelper::getListKey('colors');

        foreach ($rows as $row) {
            $option = Option::forceCreate([
                'type' => request('type'),
                'name' => Arr::get($row, 'name'),
                'slug' => Str::slug(Arr::get($row, 'name')),
                'description' => Arr::get($row, 'description'),
                'meta' => [
                    'color' => Arr::random($colors),
                ],
                'team_id' => request()->boolean('team') ? auth()->user()?->current_team_id : null,
            ]);
        }

        activity()->enableLogging();
    }

    private function validate(Collection $rows)
    {
        $options = Option::query()
            ->when(request('team'), function ($q) {
                $q->byTeam();
            })
            ->where('type', request('type'))
            ->select('name')->get()->pluck('name')->all();

        $errors = [];

        $newOptions = [];
        foreach ($rows as $index => $row) {
            $rowNo = (int) $index + 2;

            $name = Arr::get($row, 'name');
            $description = Arr::get($row, 'description');

            if (! $name) {
                $errors[] = $this->setError($rowNo, trans('option.props.name'), 'required');
            }

            if ($name && strlen($name) < 2 || strlen($name) > 500) {
                $errors[] = $this->setError($rowNo, trans('option.props.name'), 'min_max', ['min' => 2, 'max' => 500]);
            }

            if ($name && in_array($name, $options)) {
                $errors[] = $this->setError($rowNo, trans('option.props.name'), 'exists');
            }

            if ($name && in_array($name, $newOptions)) {
                $errors[] = $this->setError($rowNo, trans('option.props.name'), 'duplicate');
            }

            $newOptions[] = $name;
        }

        return $errors;
    }
}
