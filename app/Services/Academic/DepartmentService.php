<?php

namespace App\Services\Academic;

use App\Domain\Academic\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DepartmentService
{
    public function preRequisite(): array
    {
        return [];
    }

    public function create(Request $request): Department
    {
        \DB::beginTransaction();

        $department = Department::forceCreate($this->formatParams($request));

        \DB::commit();

        return $department;
    }

    private function formatParams(Request $request, ?Department $department = null): array
    {
        $formatted = [
            'name' => $request->name,
            'code' => $request->code,
            'shortcode' => $request->shortcode,
            'alias' => $request->alias,
            'description' => $request->description,
        ];

        $config = $department?->config ?? [];

        $formatted['config'] = $config;

        if (! $department) {
            $formatted['team_id'] = auth()->user()?->current_team_id;
        }

        return $formatted;
    }

    public function update(Request $request, Department $department): void
    {
        \DB::beginTransaction();

        $department->forceFill($this->formatParams($request, $department))->save();

        \DB::commit();
    }

    public function deletable(Department $department, $validate = false): ?bool
    {
        $programExists = \DB::table('programs')
            ->whereDepartmentId($department->id)
            ->exists();

        if ($programExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('academic.department.department'), 'dependency' => trans('academic.program.program')])]);
        }

        return true;
    }
}
