<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\FeeAllocationRequest;
use App\Models\Finance\FeeStructure;
use App\Services\Finance\FeeStructureActionService;
use Illuminate\Http\Request;

class FeeStructureActionController extends Controller
{
    public function allocation(FeeAllocationRequest $request, FeeStructure $feeStructure, FeeStructureActionService $service)
    {
        $feeStructure->load('period');

        $this->authorize('update', $feeStructure);

        $service->allocation($request, $feeStructure);

        return response()->success([
            'message' => trans('global.allocated', ['attribute' => trans('finance.fee_structure.fee_structure')]),
        ]);
    }

    public function removeAllocation(Request $request, FeeStructure $feeStructure, string $allocation, FeeStructureActionService $service)
    {
        $feeStructure->load('period');

        $this->authorize('update', $feeStructure);

        $service->removeAllocation($request, $feeStructure, $allocation);

        return response()->success([
            'message' => trans('global.removed', ['attribute' => trans('finance.fee_structure.allocation')]),
        ]);
    }
}
