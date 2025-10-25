<?php

namespace App\Imports\Employee;

use App\Concerns\ItemImport;
use App\Models\Employee\Designation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DesignationImport implements ToCollection, WithHeadingRow
{
    use ItemImport;

    protected $limit = 100;

    public function collection(Collection $rows)
    {
        $this->validateHeadings();

        if (count($rows) > $this->limit) {
            throw ValidationException::withMessages(['message' => trans('general.errors.max_import_limit_crossed', ['attribute' => $this->limit])]);
        }

        $logFile = $this->getLogFile('designation');

        $errors = $this->validate($rows);

        $this->checkForErrors('designation', $errors);

        if (! request()->boolean('validate') && ! \Storage::disk('local')->exists($logFile)) {
            $this->import($rows);
        }
    }

    private function import(Collection $rows)
    {
        activity()->disableLogging();

        $designations = Designation::byTeam()->get();

        foreach ($rows as $row) {
            $parent = Arr::get($row, 'parent');

            $parentId = $parent ? $designations->firstWhere('name', $parent)?->id : null;

            $designation = Designation::forceCreate([
                'team_id' => auth()->user()?->current_team_id,
                'name' => Arr::get($row, 'name'),
                'parent_id' => $parentId,
                'alias' => Arr::get($row, 'alias'),
                'description' => Arr::get($row, 'description'),
            ]);

            $designations->push($designation);
        }

        activity()->enableLogging();
    }

    private function validate(Collection $rows)
    {
        $existingNames = Designation::byTeam()->pluck('name')->all();
        $existingAliases = Designation::byTeam()->pluck('alias')->all();

        $errors = [];

        $newNames = [];
        $newAliases = [];
        foreach ($rows as $index => $row) {
            $rowNo = $index + 2;

            $name = Arr::get($row, 'name');
            $alias = Arr::get($row, 'alias');
            $parent = Arr::get($row, 'parent');
            $description = Arr::get($row, 'description');

            if (! $name) {
                $errors[] = $this->setError($rowNo, trans('employee.designation.props.name'), 'required');
            } elseif (strlen($name) < 2 || strlen($name) > 100) {
                $errors[] = $this->setError($rowNo, trans('employee.designation.props.name'), 'min_max', ['min' => 2, 'max' => 100]);
            } elseif (in_array($name, $existingNames)) {
                $errors[] = $this->setError($rowNo, trans('employee.designation.props.name'), 'exists');
            } elseif (in_array($name, $newNames)) {
                $errors[] = $this->setError($rowNo, trans('employee.designation.props.name'), 'duplicate');
            }

            if ($parent) {
                if (! in_array($parent, $existingNames) && ! in_array($parent, $newNames)) {
                    $errors[] = $this->setError($rowNo, trans('employee.designation.props.parent'), 'invalid');
                }
            }

            if ($alias) {
                if (strlen($alias) < 2 || strlen($alias) > 100) {
                    $errors[] = $this->setError($rowNo, trans('employee.designation.props.alias'), 'min_max', ['min' => 2, 'max' => 100]);
                } elseif (in_array($alias, $existingAliases)) {
                    $errors[] = $this->setError($rowNo, trans('employee.designation.props.alias'), 'exists');
                } elseif (in_array($alias, $newAliases)) {
                    $errors[] = $this->setError($rowNo, trans('employee.designation.props.alias'), 'duplicate');
                }
            }

            if ($description && (strlen($description) < 2 || strlen($description) > 1000)) {
                $errors[] = $this->setError($rowNo, trans('employee.designation.props.description'), 'min_max', ['min' => 2, 'max' => 100]);
            }

            $newNames[] = $name;
            $newAliases[] = $alias;
        }

        return $errors;
    }
}
