<?php

namespace App\Services\Finance;

use App\Enums\Finance\DefaultCustomFeeType;
use App\Http\Resources\Finance\FeeGroupResource;
use App\Models\Finance\FeeGroup;
use App\Models\Finance\FeeHead;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FeeHeadService
{
    public function preRequisite(Request $request)
    {
        $feeGroups = FeeGroupResource::collection(FeeGroup::query()
            ->byPeriod()
            ->get());

        $types = DefaultCustomFeeType::getOptions();

        return compact('feeGroups', 'types');
    }

    public function findByUuidOrFail(string $uuid): FeeHead
    {
        return FeeHead::query()
            ->byPeriod()
            ->findByUuidOrFail($uuid, trans('finance.fee_head.fee_head'), 'message');
    }

    public function create(Request $request): FeeHead
    {
        \DB::beginTransaction();

        $feeHead = FeeHead::forceCreate($this->formatParams($request));

        \DB::commit();

        return $feeHead;
    }

    private function formatParams(Request $request, ?FeeHead $feeHead = null): array
    {
        $formatted = [
            'name' => $request->name,
            'fee_group_id' => $request->fee_group_id,
            'type' => $request->type,
            'description' => $request->description,
        ];

        if (! $feeHead) {
            $formatted['period_id'] = auth()->user()->current_period_id;
        }

        return $formatted;
    }

    public function update(Request $request, FeeHead $feeHead): void
    {
        $feeInstallmentExists = \DB::table('fee_installment_records')
            ->whereFeeHeadId($feeHead->id)
            ->exists();

        if ($feeInstallmentExists && $feeHead?->fee_group_id != $request->fee_group_id) {
            throw ValidationException::withMessages(['message' => trans('finance.fee_head.could_not_modify_if_installment_exists')]);
        }

        if (! $feeHead->fee_group_id && $request->fee_group_id) {
            $feeInstallmentExists = \DB::table('fee_installments')
                ->whereFeeGroupId($request->fee_group_id)
                ->exists();

            if ($feeInstallmentExists) {
                throw ValidationException::withMessages(['message' => trans('finance.fee_head.could_not_modify_if_installment_exists')]);
            }
        }

        \DB::beginTransaction();

        $feeHead->forceFill($this->formatParams($request, $feeHead))->save();

        \DB::commit();
    }

    public function deletable(FeeHead $feeHead): bool
    {
        $feeConcessionExists = \DB::table('fee_concession_records')
            ->whereFeeHeadId($feeHead->id)
            ->exists();

        if ($feeConcessionExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('finance.fee_head.fee_head'), 'dependency' => trans('finance.fee_concession.fee_concession')])]);
        }

        $feeInstallmentExists = \DB::table('fee_installment_records')
            ->whereFeeHeadId($feeHead->id)
            ->exists();

        if ($feeInstallmentExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('finance.fee_head.fee_head'), 'dependency' => trans('finance.fee_structure.installment')])]);
        }

        return true;
    }
}
