<?php

namespace App\Services\Academic;

use App\Models\Incharge;
use Illuminate\Http\Request;

class BatchInchargeService
{
    public function preRequisite(Request $request)
    {
        return [];
    }

    public function create(Request $request): Incharge
    {
        \DB::beginTransaction();

        $batchIncharge = Incharge::forceCreate($this->formatParams($request));

        \DB::commit();

        return $batchIncharge;
    }

    private function formatParams(Request $request, ?Incharge $batchIncharge = null): array
    {
        $formatted = [
            'model_type' => 'Batch',
            'model_id' => $request->batch_id,
            'employee_id' => $request->employee_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'remarks' => $request->remarks,
        ];

        if (! $batchIncharge) {
            //
        }

        return $formatted;
    }

    public function update(Request $request, Incharge $batchIncharge): void
    {
        \DB::beginTransaction();

        $batchIncharge->forceFill($this->formatParams($request, $batchIncharge))->save();

        \DB::commit();
    }

    public function deletable(Incharge $batchIncharge): void
    {
        //
    }
}
