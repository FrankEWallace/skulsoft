<?php

namespace App\Services\Academic;

use App\Http\Resources\Academic\DivisionResource;
use App\Models\Academic\Division;
use App\Models\Incharge;
use Illuminate\Http\Request;

class DivisionInchargeService
{
    public function preRequisite(Request $request)
    {
        $divisions = DivisionResource::collection(Division::query()
            ->select('divisions.*', 'programs.name as program_name')
            ->leftJoin('programs', 'programs.id', '=', 'divisions.program_id')
            ->byPeriod()
            ->filterAccessible()
            ->get());

        return compact('divisions');
    }

    public function create(Request $request): Incharge
    {
        \DB::beginTransaction();

        $divisionIncharge = Incharge::forceCreate($this->formatParams($request));

        \DB::commit();

        return $divisionIncharge;
    }

    private function formatParams(Request $request, ?Incharge $divisionIncharge = null): array
    {
        $formatted = [
            'model_type' => 'Division',
            'model_id' => $request->division_id,
            'employee_id' => $request->employee_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'remarks' => $request->remarks,
        ];

        if (! $divisionIncharge) {
            //
        }

        return $formatted;
    }

    public function update(Request $request, Incharge $divisionIncharge): void
    {
        \DB::beginTransaction();

        $divisionIncharge->forceFill($this->formatParams($request, $divisionIncharge))->save();

        \DB::commit();
    }

    public function deletable(Incharge $divisionIncharge): void
    {
        //
    }
}
