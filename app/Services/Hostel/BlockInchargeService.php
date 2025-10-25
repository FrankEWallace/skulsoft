<?php

namespace App\Services\Hostel;

use App\Http\Resources\Hostel\BlockResource;
use App\Models\Hostel\Block;
use App\Models\Incharge;
use Illuminate\Http\Request;

class BlockInchargeService
{
    public function preRequisite(Request $request)
    {
        $blocks = BlockResource::collection(Block::query()
            ->byTeam()
            ->hostel()
            ->get());

        return compact('blocks');
    }

    public function create(Request $request): Incharge
    {
        \DB::beginTransaction();

        $blockIncharge = Incharge::forceCreate($this->formatParams($request));

        \DB::commit();

        return $blockIncharge;
    }

    private function formatParams(Request $request, ?Incharge $blockIncharge = null): array
    {
        $formatted = [
            'model_type' => 'HostelBlock',
            'model_id' => $request->block_id,
            'employee_id' => $request->employee_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'remarks' => $request->remarks,
        ];

        if (! $blockIncharge) {
            //
        }

        return $formatted;
    }

    public function update(Request $request, Incharge $blockIncharge): void
    {
        \DB::beginTransaction();

        $blockIncharge->forceFill($this->formatParams($request, $blockIncharge))->save();

        \DB::commit();
    }

    public function deletable(Incharge $blockIncharge): void
    {
        //
    }
}
