<?php

namespace App\Services\Academic;

use App\Http\Resources\Academic\ProgramResource;
use App\Domain\Academic\Models\Program;
use App\Models\Incharge;
use Illuminate\Http\Request;

class ProgramInchargeService
{
    public function preRequisite(Request $request)
    {
        $programs = ProgramResource::collection(Program::query()
            ->select('programs.*', 'academic_departments.name as department_name')
            ->leftJoin('academic_departments', 'academic_departments.id', '=', 'programs.department_id')
            ->byTeam()
            ->get());

        return compact('programs');
    }

    public function create(Request $request): Incharge
    {
        \DB::beginTransaction();

        $programIncharge = Incharge::forceCreate($this->formatParams($request));

        \DB::commit();

        return $programIncharge;
    }

    private function formatParams(Request $request, ?Incharge $programIncharge = null): array
    {
        $formatted = [
            'model_type' => 'Program',
            'model_id' => $request->program_id,
            'employee_id' => $request->employee_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'remarks' => $request->remarks,
        ];

        if (! $programIncharge) {
            //
        }

        return $formatted;
    }

    public function update(Request $request, Incharge $programIncharge): void
    {
        \DB::beginTransaction();

        $programIncharge->forceFill($this->formatParams($request, $programIncharge))->save();

        \DB::commit();
    }

    public function deletable(Incharge $programIncharge): void
    {
        //
    }
}
