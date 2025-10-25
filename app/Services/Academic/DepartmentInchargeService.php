<?php

namespace App\Services\Academic;

use App\Http\Resources\Academic\DepartmentResource;
use App\Models\Academic\Department;
use App\Models\Incharge;
use Illuminate\Http\Request;

class DepartmentInchargeService
{
    public function preRequisite(Request $request)
    {
        $departments = DepartmentResource::collection(Department::query()
            ->byTeam()
            ->get());

        return compact('departments');
    }

    public function create(Request $request): Incharge
    {
        \DB::beginTransaction();

        $departmentIncharge = Incharge::forceCreate($this->formatParams($request));

        \DB::commit();

        return $departmentIncharge;
    }

    private function formatParams(Request $request, ?Incharge $departmentIncharge = null): array
    {
        $formatted = [
            'model_type' => 'AcademicDepartment',
            'model_id' => $request->department_id,
            'employee_id' => $request->employee_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'remarks' => $request->remarks,
        ];

        if (! $departmentIncharge) {
            //
        }

        return $formatted;
    }

    public function update(Request $request, Incharge $departmentIncharge): void
    {
        \DB::beginTransaction();

        $departmentIncharge->forceFill($this->formatParams($request, $departmentIncharge))->save();

        \DB::commit();
    }

    public function deletable(Incharge $departmentIncharge): void
    {
        //
    }
}
