<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\FeeInstallmentRequest;
use App\Http\Resources\Finance\FeeInstallmentResource;
use App\Models\Finance\FeeInstallment;
use App\Models\Finance\FeeStructure;
use App\Services\Finance\FeeInstallmentService;

class FeeInstallmentController extends Controller
{
    public function create(FeeInstallmentRequest $request, string $feeStructure, FeeInstallmentService $service)
    {
        $feeStructure = FeeStructure::findByUuidOrFail($feeStructure);

        $feeStructure->load('period');

        $this->authorize('update', $feeStructure);

        $service->create($feeStructure, $request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('finance.fee_structure.installment')]),
        ]);
    }

    public function show(string $feeStructure, $uuid)
    {
        $feeStructure = FeeStructure::findByUuidOrFail($feeStructure);

        $feeStructure->load('period');

        $this->authorize('view', $feeStructure);

        $feeInstallment = FeeInstallment::query()
            ->with('records.head', 'group.heads', 'transportFee')
            ->whereFeeStructureId($feeStructure->id)
            ->findByUuidOrFail($uuid);

        return FeeInstallmentResource::make($feeStructure);
    }

    public function update(FeeInstallmentRequest $request, string $feeStructure, $uuid, FeeInstallmentService $service)
    {
        $feeStructure = FeeStructure::query()
            ->withCount(['students as assigned_students'])
            ->findByUuidOrFail($feeStructure);

        $feeStructure->load('period');

        $this->authorize('update', $feeStructure);

        $service->update($feeStructure, $uuid, $request);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('finance.fee_structure.installment')]),
        ]);
    }

    public function destroy(string $feeStructure, string $uuid, FeeInstallmentService $service)
    {
        $feeStructure = FeeStructure::findByUuidOrFail($feeStructure);

        $feeStructure->load('period');

        $this->authorize('update', $feeStructure);

        $service->delete($feeStructure, $uuid);

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('finance.fee_structure.installment')]),
        ]);
    }
}
