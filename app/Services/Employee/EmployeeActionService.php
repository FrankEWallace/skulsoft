<?php

namespace App\Services\Employee;

use App\Actions\CreateTag;
use App\Enums\OptionType;
use App\Models\Employee\Employee;
use App\Models\GroupMember;
use App\Models\Option;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class EmployeeActionService
{
    public function updateTags(Request $request, Employee $employee)
    {
        $request->validate([
            'tags' => 'array',
            'tags.*' => 'required|string|distinct',
        ]);

        $tags = (new CreateTag)->execute($request->input('tags', []));

        $employee->tags()->sync($tags);
    }

    private function getBulkEmployees(Request $request): array
    {
        $selectAll = $request->boolean('select_all');

        $employees = [];

        if ($selectAll) {
            $employees = (new EmployeeListService)->listAll($request)->toArray();
        } else {
            $employees = Employee::query()
                ->whereIn('uuid', $request->employees)
                ->filterAccessible()
                ->get()
                ->toArray();

            if (array_diff($request->employees, Arr::pluck($employees, 'uuid'))) {
                throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
            }
        }

        $uniqueEmployees = array_unique(Arr::pluck($employees, 'uuid'));

        if (count($uniqueEmployees) !== count($employees)) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
        }

        return $employees;
    }

    public function updateBulkTags(Request $request)
    {
        $request->validate([
            'action' => 'required|string|in:assign,remove',
            'employees' => 'array',
            'tags' => 'array',
            'tags.*' => 'required|string|distinct',
        ]);

        $employees = $this->getBulkEmployees($request);

        $tags = (new CreateTag)->execute($request->input('tags', []));

        if ($request->input('action') === 'assign') {
            foreach ($employees as $employee) {
                Employee::query()
                    ->byTeam()
                    ->whereUuid(Arr::get($employee, 'uuid'))
                    ->first()
                    ->tags()
                    ->sync($tags);
            }
        } else {
            foreach ($employees as $employee) {
                Employee::query()
                    ->byTeam()
                    ->whereUuid(Arr::get($employee, 'uuid'))
                    ->first()
                    ->tags()
                    ->detach($tags);
            }
        }
    }

    public function updateBulkGroups(Request $request)
    {
        $request->validate([
            'action' => 'required|string|in:assign,remove',
            'employees' => 'array',
            'groups' => 'array',
            'groups.*' => 'required|uuid|distinct',
        ]);

        $employeeGroup = Option::query()
            ->byTeam()
            ->where('type', OptionType::EMPLOYEE_GROUP)
            ->whereIn('uuid', $request->input('groups', []))
            ->get();

        if (! $employeeGroup->count()) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
        }

        $employees = $this->getBulkEmployees($request);

        if ($request->input('action') === 'assign') {
            foreach ($employees as $employee) {
                foreach ($employeeGroup as $group) {
                    GroupMember::firstOrCreate([
                        'model_type' => 'Employee',
                        'model_id' => Arr::get($employee, 'id'),
                        'model_group_id' => $group->id,
                    ]);
                }
            }
        } else {
            foreach ($employees as $employee) {
                GroupMember::where('model_type', 'Employee')
                    ->where('model_id', Arr::get($employee, 'id'))
                    ->whereIn('model_group_id', $employeeGroup->pluck('id'))
                    ->delete();
            }
        }
    }
}
