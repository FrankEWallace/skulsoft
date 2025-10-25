<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\FeeStructureRequest;
use App\Http\Resources\Finance\FeeStructureResource;
use App\Models\Finance\FeeStructure;
use App\Services\Finance\FeeStructureListService;
use App\Services\Finance\FeeStructureService;
use Illuminate\Http\Request;

class FeeStructureController extends Controller
{
    public function preRequisite(FeeStructureService $service)
    {
        return response()->ok($service->preRequisite());
    }

    public function index(Request $request, FeeStructureListService $service)
    {
        $this->authorize('viewAny', FeeStructure::class);

        return $service->paginate($request);
    }

    public function store(FeeStructureRequest $request, FeeStructureService $service)
    {
        $this->authorize('create', FeeStructure::class);

        $feeStructure = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('finance.fee_structure.fee_structure')]),
            'fee_structure' => FeeStructureResource::make($feeStructure),
        ]);
    }

    public function show(string $feeStructure, FeeStructureService $service): FeeStructureResource
    {
        $feeStructure = FeeStructure::query()
            ->withCount(['students as assigned_students'])
            ->findByUuidOrFail($feeStructure);

        $this->authorize('view', $feeStructure);

        $feeStructure->load(['period', 'allocations.course', 'allocations.batch.course', 'installments.group.heads', 'installments.transportFee', 'installments.records.head']);

        return FeeStructureResource::make($feeStructure);
    }

    public function update(FeeStructureRequest $request, string $feeStructure, FeeStructureService $service)
    {
        $feeStructure = FeeStructure::findByUuidOrFail($feeStructure);

        $this->authorize('update', $feeStructure);

        $service->update($request, $feeStructure);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('finance.fee_structure.fee_structure')]),
        ]);
    }

    public function destroy(string $feeStructure, FeeStructureService $service)
    {
        $feeStructure = FeeStructure::findByUuidOrFail($feeStructure);

        $this->authorize('delete', $feeStructure);

        $service->deletable($feeStructure);

        $feeStructure->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('finance.fee_structure.fee_structure')]),
        ]);
    }
}
