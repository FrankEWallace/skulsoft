<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\FeeAllocationRequest;
use App\Models\Student\Student;
use App\Services\Student\FeeAllocationService;
use Illuminate\Http\Request;

class FeeAllocationController extends Controller
{
    public function preRequisite(Request $request, FeeAllocationService $service)
    {
        $this->authorize('setBatchFeeAllocation', Student::class);

        return response()->ok($service->preRequisite($request));
    }

    public function fetch(Request $request, FeeAllocationService $service)
    {
        $this->authorize('setBatchFeeAllocation', Student::class);

        return $service->fetch($request);
    }

    public function allocate(FeeAllocationRequest $request, FeeAllocationService $service)
    {
        $this->authorize('setBatchFeeAllocation', Student::class);

        $service->allocate($request);

        return response()->success([
            'message' => trans('global.allocated', ['attribute' => trans('student.fee.fee')]),
        ]);
    }

    public function allocateFeeConcessionType(FeeAllocationRequest $request, FeeAllocationService $service)
    {
        $this->authorize('setBatchFeeAllocation', Student::class);

        $service->allocateFeeConcessionType($request);

        return response()->success([
            'message' => trans('global.allocated', ['attribute' => trans('student.fee.fee')]),
        ]);
    }

    public function remove(FeeAllocationRequest $request, FeeAllocationService $service)
    {
        $this->authorize('setBatchFeeAllocation', Student::class);

        $service->remove($request);

        return response()->success([
            'message' => trans('global.removed', ['attribute' => trans('student.fee.fee')]),
        ]);
    }
}
