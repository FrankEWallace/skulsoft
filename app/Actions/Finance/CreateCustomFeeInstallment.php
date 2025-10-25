<?php

namespace App\Actions\Finance;

use App\Models\Finance\FeeGroup;
use App\Models\Finance\FeeInstallment;
use Illuminate\Validation\ValidationException;

class CreateCustomFeeInstallment
{
    public function execute(?int $feeStructureId = null): ?FeeInstallment
    {
        if (! $feeStructureId) {
            throw ValidationException::withMessages(['message' => trans('student.fee.fee_not_set')]);
        }

        $feeGroup = FeeGroup::query()
            ->byPeriod()
            ->where('meta->is_custom', '=', true)
            ->first();

        if (! $feeGroup) {
            return null;
        }

        $feeInstallment = FeeInstallment::query()
            ->where('fee_structure_id', $feeStructureId)
            ->where('fee_group_id', $feeGroup->id)
            ->first();

        if ($feeInstallment) {
            return $feeInstallment;
        }

        $feeInstallment = FeeInstallment::forceCreate([
            'fee_structure_id' => $feeStructureId,
            'fee_group_id' => $feeGroup->id,
            'title' => trans('finance.fee_head.custom_fee'),
            'meta' => ['is_custom' => true],
        ]);

        return $feeInstallment;
    }
}
