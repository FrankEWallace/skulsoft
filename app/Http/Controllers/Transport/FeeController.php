<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transport\FeeRequest;
use App\Http\Resources\Transport\FeeResource;
use App\Models\Transport\Fee;
use App\Services\Transport\FeeListService;
use App\Services\Transport\FeeService;
use Illuminate\Http\Request;

class FeeController extends Controller
{
    public function preRequisite(FeeService $service)
    {
        return response()->ok($service->preRequisite());
    }

    public function index(Request $request, FeeListService $service)
    {
        $this->authorize('viewAny', Fee::class);

        return $service->paginate($request);
    }

    public function store(FeeRequest $request, FeeService $service)
    {
        $this->authorize('create', Fee::class);

        $fee = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('transport.fee.fee')]),
            'fee' => FeeResource::make($fee),
        ]);
    }

    public function show(string $fee, FeeService $service): FeeResource
    {
        $fee = $service->findByUuidOrFail($fee);

        $this->authorize('view', $fee);

        $fee->load('records.circle');

        $fee->is_assigned = $service->isAssigned($fee);

        return FeeResource::make($fee);
    }

    public function update(FeeRequest $request, string $fee, FeeService $service)
    {
        $fee = $service->findByUuidOrFail($fee);

        $this->authorize('update', $fee);

        $service->update($request, $fee);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('transport.fee.fee')]),
        ]);
    }

    public function destroy(string $fee, FeeService $service)
    {
        $fee = $service->findByUuidOrFail($fee);

        $this->authorize('delete', $fee);

        $service->deletable($fee);

        $fee->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('transport.fee.fee')]),
        ]);
    }
}
