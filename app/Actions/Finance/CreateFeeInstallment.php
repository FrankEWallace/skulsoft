<?php

namespace App\Actions\Finance;

use App\Models\Finance\FeeInstallment;
use App\Models\Finance\FeeInstallmentRecord;
use App\Models\Finance\FeeStructure;
use Illuminate\Support\Arr;

class CreateFeeInstallment
{
    public function execute(FeeStructure $feeStructure, ?FeeInstallment $feeInstallment = null, array $params = []): FeeInstallment
    {
        $feeInstallment = $feeInstallment ?? $this->getFeeInstallment($feeStructure, $params);

        $feeInstallment->fee_group_id = Arr::get($params, 'fee_group_id');
        $feeInstallment->title = Arr::get($params, 'title');
        $feeInstallment->due_date = Arr::get($params, 'due_date');
        $feeInstallment->late_fee = Arr::get($params, 'late_fee');
        $feeInstallment->transport_fee_id = Arr::get($params, 'has_transport_fee') ? Arr::get($params, 'transport_fee_id') : null;
        $feeInstallment->setMeta([
            'has_no_concession' => Arr::get($params, 'has_no_concession'),
        ]);
        $feeInstallment->save();

        foreach (Arr::get($params, 'heads', []) as $feeHead) {
            $feeInstallmentRecord = FeeInstallmentRecord::firstOrCreate([
                'fee_installment_id' => $feeInstallment->id,
                'fee_head_id' => Arr::get($feeHead, 'id'),
            ]);

            $feeInstallmentRecord->amount = Arr::get($feeHead, 'amount', 0);
            $feeInstallmentRecord->is_optional = (bool) Arr::get($feeHead, 'is_optional');

            $feeInstallmentRecord->setMeta([
                'applicable_to' => Arr::get($feeHead, 'applicable_to', 'all'),
                'applicable_to_gender' => Arr::get($feeHead, 'applicable_to_gender', 'all'),
            ]);

            $feeInstallmentRecord->save();
        }

        return $feeInstallment;
    }

    private function getFeeInstallment(FeeStructure $feeStructure, array $params = []): FeeInstallment
    {
        $action = Arr::get($params, 'action', 'create');

        if ($action == 'create') {
            return FeeInstallment::forceCreate([
                'fee_structure_id' => $feeStructure->id,
            ]);
        }

        $feeInstallment = FeeInstallment::query()
            ->whereFeeStructureId($feeStructure->id)
            ->whereUuid(Arr::get($params, 'uuid'))
            ->first();

        if (! $feeInstallment) {
            return FeeInstallment::forceCreate([
                'fee_structure_id' => $feeStructure->id,
            ]);
        }

        return $feeInstallment;
    }
}
