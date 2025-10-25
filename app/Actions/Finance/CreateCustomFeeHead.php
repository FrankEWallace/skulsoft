<?php

namespace App\Actions\Finance;

use App\Models\Finance\FeeGroup;

class CreateCustomFeeHead
{
    public function execute(): ?FeeGroup
    {
        $feeGroup = FeeGroup::query()
            ->byPeriod()
            ->where('meta->is_custom', '=', true)
            ->first();

        if ($feeGroup) {
            return $feeGroup;
        }

        $feeGroup = FeeGroup::query()
            ->firstOrCreate([
                'period_id' => auth()->user()->current_period_id,
                'name' => trans('finance.fee_head.custom_fee'),
            ]);

        $feeGroup->setMeta(['is_custom' => true]);
        $feeGroup->save();

        return $feeGroup;
    }
}
