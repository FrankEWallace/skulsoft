<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\ServiceAllocationRequest;
use App\Models\Student\Student;
use App\Services\Student\ServiceAllocationService;
use Illuminate\Http\Request;

class ServiceAllocationController extends Controller
{
    public function preRequisite(Request $request, ServiceAllocationService $service)
    {
        $this->authorize('setBatchServiceAllocation', Student::class);

        return response()->ok($service->preRequisite($request));
    }

    public function fetch(Request $request, ServiceAllocationService $service)
    {
        $this->authorize('setBatchServiceAllocation', Student::class);

        return $service->fetch($request);
    }

    public function allocate(ServiceAllocationRequest $request, ServiceAllocationService $service)
    {
        $this->authorize('setBatchServiceAllocation', Student::class);

        $service->allocate($request);

        return response()->success([
            'message' => trans('global.allocated', ['attribute' => trans('student.service.service')]),
        ]);
    }

    public function remove(ServiceAllocationRequest $request, ServiceAllocationService $service)
    {
        $this->authorize('setBatchServiceAllocation', Student::class);

        $service->remove($request);

        return response()->success([
            'message' => trans('global.removed', ['attribute' => trans('student.service.service')]),
        ]);
    }
}
