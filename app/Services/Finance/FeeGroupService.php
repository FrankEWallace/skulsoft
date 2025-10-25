<?php

namespace App\Services\Finance;

use App\Models\Finance\FeeGroup;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FeeGroupService
{
    public function preRequisite(): array
    {
        return [];
    }

    public function findByUuidOrFail(string $uuid): FeeGroup
    {
        return FeeGroup::query()
            ->byPeriod()
            ->findByUuidOrFail($uuid, trans('finance.fee_group.fee_group'), 'message');
    }

    public function create(Request $request): FeeGroup
    {
        \DB::beginTransaction();

        $feeGroup = FeeGroup::forceCreate($this->formatParams($request));

        \DB::commit();

        return $feeGroup;
    }

    private function formatParams(Request $request, ?FeeGroup $feeGroup = null): array
    {
        $formatted = [
            'name' => $request->name,
            'description' => $request->description,
        ];

        $meta = $feeGroup?->meta;

        $meta['pg_account'] = $request->pg_account;

        if (! $feeGroup) {
            $formatted['period_id'] = auth()->user()->current_period_id;
        }

        $formatted['meta'] = $meta;

        return $formatted;
    }

    public function update(Request $request, FeeGroup $feeGroup): void
    {
        \DB::beginTransaction();

        $feeGroup->forceFill($this->formatParams($request, $feeGroup))->save();

        \DB::commit();
    }

    public function deletable(FeeGroup $feeGroup, $validate = false): bool
    {
        $feeHeadExists = \DB::table('fee_heads')
            ->whereFeeGroupId($feeGroup->id)
            ->exists();

        if ($feeHeadExists) {
            throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('finance.fee_group.fee_group'), 'dependency' => trans('finance.fee_head.fee_head')])]);
        }

        return true;
    }
}
