<?php

namespace App\Services\Academic;

use App\Models\Academic\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class DepartmentActionService
{
    public function updateConfig(Request $request, Department $department): void
    {
        //
    }

    public function reorder(Request $request): void
    {
        $departments = $request->departments ?? [];

        $allDepartments = Department::query()
            ->byTeam()
            ->get();

        foreach ($departments as $index => $departmentItem) {
            $department = $allDepartments->firstWhere('uuid', Arr::get($departmentItem, 'uuid'));

            if (! $department) {
                continue;
            }

            $department->position = $index + 1;
            $department->save();
        }
    }
}
