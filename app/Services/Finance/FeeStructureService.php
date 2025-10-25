<?php

namespace App\Services\Finance;

use App\Actions\Finance\CreateCustomFeeInstallment;
use App\Actions\Finance\CreateFeeInstallment;
use App\Enums\Finance\LateFeeFrequency;
use App\Http\Resources\Finance\FeeGroupResource;
use App\Http\Resources\Transport\FeeResource as TransportFeeResource;
use App\Models\Finance\FeeGroup;
use App\Models\Finance\FeeInstallment;
use App\Models\Finance\FeeStructure;
use App\Models\Student\Student;
use App\Models\Transport\Fee as TransportFee;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class FeeStructureService
{
    public function preRequisite(): array
    {
        $transportFees = TransportFeeResource::collection(TransportFee::query()
            ->byPeriod()
            ->get());

        $feeGroups = FeeGroupResource::collection(FeeGroup::query()
            ->with('heads')
            ->byPeriod()
            ->get()
            ->filter(function ($feeGroup) {
                return ! $feeGroup->getMeta('is_custom');
            }));

        $frequencies = LateFeeFrequency::getOptions();

        return compact('transportFees', 'frequencies', 'feeGroups');
    }

    public function create(Request $request): FeeStructure
    {
        \DB::beginTransaction();

        $feeStructure = FeeStructure::forceCreate($this->formatParams($request));

        $this->updateInstallments($request, $feeStructure);

        \DB::commit();

        return $feeStructure;
    }

    private function formatParams(Request $request, ?FeeStructure $feeStructure = null): array
    {
        $formatted = [
            'name' => $request->name,
            'description' => $request->description,
        ];

        if (! $feeStructure) {
            $formatted['period_id'] = auth()->user()->current_period_id;
        }

        return $formatted;
    }

    private function updateInstallments(Request $request, FeeStructure $feeStructure, $action = 'create'): void
    {
        $feeInstallmentUuids = [];

        foreach ($request->fee_groups as $feeGroup) {
            foreach (Arr::get($feeGroup, 'installments', []) as $params) {
                $params['action'] = $action;
                $params['fee_group_id'] = Arr::get($feeGroup, 'id');

                $feeInstallment = (new CreateFeeInstallment)->execute(feeStructure: $feeStructure, params: $params);
                $feeInstallmentUuids[] = $feeInstallment->uuid;
            }
        }

        $customFeeInstallment = (new CreateCustomFeeInstallment)->execute($feeStructure->id);
        if ($customFeeInstallment) {
            $feeInstallmentUuids[] = $customFeeInstallment->uuid;
        }

        FeeInstallment::whereFeeStructureId($feeStructure->id)->whereNotIn('uuid', $feeInstallmentUuids)->delete();
    }

    public function update(Request $request, FeeStructure $feeStructure): void
    {
        // $feeAllocationExists = \DB::table('fee_allocations')
        //     ->whereFeeStructureId($feeStructure->id)
        //     ->exists();

        // if ($feeAllocationExists) {
        //     throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('finance.fee_structure.fee_structure'), 'dependency' => trans('finance.fee_structure.allocation')])]);
        // }

        if (Student::query()->whereFeeStructureId($feeStructure->id)->exists()) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('finance.fee_structure.fee_structure'), 'dependency' => trans('student.student')])]);
        }

        \DB::beginTransaction();

        $feeStructure->forceFill($this->formatParams($request, $feeStructure))->save();

        $this->updateInstallments($request, $feeStructure, 'update');

        \DB::commit();
    }

    public function deletable(FeeStructure $feeStructure, $validate = false): ?bool
    {
        $feeAllocationExists = \DB::table('fee_allocations')
            ->whereFeeStructureId($feeStructure->id)
            ->exists();

        if ($feeAllocationExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('finance.fee_structure.fee_structure'), 'dependency' => trans('finance.fee_structure.allocation')])]);
        }

        $studentExists = \DB::table('students')
            ->whereFeeStructureId($feeStructure->id)
            ->exists();

        if ($studentExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('finance.fee_structure.fee_structure'), 'dependency' => trans('student.student')])]);
        }

        return true;
    }
}
