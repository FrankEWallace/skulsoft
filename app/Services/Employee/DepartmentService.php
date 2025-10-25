<?php

namespace App\Services\Employee;

use App\Models\Employee\Attendance\Record;
use App\Models\Employee\Department;
use App\Models\Employee\Record as EmployeeRecord;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DepartmentService
{
    public function preRequisite(Request $request): array
    {
        return [];
    }

    public function create(Request $request): Department
    {
        // throw ValidationException::withMessages(['message' => 'test']);

        \DB::beginTransaction();

        $department = Department::forceCreate($this->formatParams($request));

        \DB::commit();

        return $department;
    }

    private function formatParams(Request $request, ?Department $department = null): array
    {
        $formatted = [
            'name' => $request->name,
            'alias' => $request->alias,
            'description' => $request->description,
        ];

        if (! $request->boolean('is_global')) {
            $formatted['team_id'] = auth()->user()?->current_team_id;
        }

        if (! $department) {
        }

        return $formatted;
    }

    public function update(Request $request, Department $department): void
    {
        if (! $department->is_global && $request->boolean('is_global')) {
            $existingDepartments = Department::query()
                ->where('team_id', '!=', $department->team_id)
                ->where('name', $department->name)
                ->exists();

            if ($existingDepartments) {
                throw ValidationException::withMessages(['message' => trans('validation.unique', ['attribute' => trans('employee.department.department')])]);
            }
        } elseif ($department->is_global && ! $request->boolean('is_global')) {
            $existingEmployeeRecords = Record::query()
                ->whereHas('employee', function ($q) {
                    $q->where('team_id', '!=', auth()->user()?->current_team_id);
                })
                ->where('department_id', $department->id)
                ->exists();

            if ($existingEmployeeRecords) {
                throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('employee.department.department'), 'dependency' => trans('employee.employee')])]);
            }
        }

        \DB::beginTransaction();

        $department->forceFill($this->formatParams($request, $department))->save();

        \DB::commit();
    }

    public function deletable(Department $department): void
    {
        $employeeRecordExists = EmployeeRecord::whereDepartmentId($department->id)->exists();

        if ($employeeRecordExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('employee.department.department'), 'dependency' => trans('employee.employee')])]);
        }
    }
}
