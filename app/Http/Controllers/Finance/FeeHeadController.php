<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\FeeHeadRequest;
use App\Http\Resources\Finance\FeeHeadResource;
use App\Models\Finance\FeeHead;
use App\Services\Finance\FeeHeadListService;
use App\Services\Finance\FeeHeadService;
use Illuminate\Http\Request;

class FeeHeadController extends Controller
{
    public function preRequisite(Request $request, FeeHeadService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, FeeHeadListService $service)
    {
        $this->authorize('viewAny', FeeHead::class);

        return $service->paginate($request);
    }

    public function store(FeeHeadRequest $request, FeeHeadService $service)
    {
        $this->authorize('create', FeeHead::class);

        $feeHead = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('finance.fee_head.fee_head')]),
            'fee_head' => FeeHeadResource::make($feeHead),
        ]);
    }

    public function show(string $feeHead, FeeHeadService $service): FeeHeadResource
    {
        $feeHead = $service->findByUuidOrFail($feeHead);

        $this->authorize('view', $feeHead);

        $feeHead->load('group');

        return FeeHeadResource::make($feeHead);
    }

    public function update(FeeHeadRequest $request, string $feeHead, FeeHeadService $service)
    {
        $feeHead = $service->findByUuidOrFail($feeHead);

        $this->authorize('update', $feeHead);

        $service->update($request, $feeHead);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('finance.fee_head.fee_head')]),
        ]);
    }

    public function destroy(string $feeHead, FeeHeadService $service)
    {
        $feeHead = $service->findByUuidOrFail($feeHead);

        $this->authorize('delete', $feeHead);

        $service->deletable($feeHead);

        $feeHead->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('finance.fee_head.fee_head')]),
        ]);
    }
}
