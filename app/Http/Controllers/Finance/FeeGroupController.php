<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\FeeGroupRequest;
use App\Http\Resources\Finance\FeeGroupResource;
use App\Models\Finance\FeeGroup;
use App\Services\Finance\FeeGroupListService;
use App\Services\Finance\FeeGroupService;
use Illuminate\Http\Request;

class FeeGroupController extends Controller
{
    public function preRequisite(FeeGroupService $service)
    {
        return response()->ok($service->preRequisite());
    }

    public function index(Request $request, FeeGroupListService $service)
    {
        $this->authorize('viewAny', FeeGroup::class);

        return $service->paginate($request);
    }

    public function store(FeeGroupRequest $request, FeeGroupService $service)
    {
        $this->authorize('create', FeeGroup::class);

        $feeGroup = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('finance.fee_group.fee_group')]),
            'fee_group' => FeeGroupResource::make($feeGroup),
        ]);
    }

    public function show(string $feeGroup, FeeGroupService $service): FeeGroupResource
    {
        $feeGroup = $service->findByUuidOrFail($feeGroup);

        $this->authorize('view', $feeGroup);

        return FeeGroupResource::make($feeGroup);
    }

    public function update(FeeGroupRequest $request, string $feeGroup, FeeGroupService $service)
    {
        $feeGroup = $service->findByUuidOrFail($feeGroup);

        $this->authorize('update', $feeGroup);

        $service->update($request, $feeGroup);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('finance.fee_group.fee_group')]),
        ]);
    }

    public function destroy(string $feeGroup, FeeGroupService $service)
    {
        $feeGroup = $service->findByUuidOrFail($feeGroup);

        $this->authorize('delete', $feeGroup);

        $service->deletable($feeGroup);

        $feeGroup->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('finance.fee_group.fee_group')]),
        ]);
    }
}
