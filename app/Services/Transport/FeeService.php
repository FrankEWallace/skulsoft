<?php

namespace App\Services\Transport;

use App\Http\Resources\Transport\CircleResource;
use App\Models\Finance\FeeInstallment;
use App\Models\Student\Fee as StudentFee;
use App\Models\Transport\Circle;
use App\Models\Transport\Fee;
use App\Models\Transport\FeeRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class FeeService
{
    public function preRequisite(): array
    {
        $circles = CircleResource::collection(Circle::query()
            ->byPeriod()
            ->get());

        return compact('circles');
    }

    public function findByUuidOrFail(string $uuid): Fee
    {
        return Fee::query()
            ->byPeriod()
            ->findByUuidOrFail($uuid, trans('transport.fee.fee'), 'message');
    }

    public function create(Request $request): Fee
    {
        \DB::beginTransaction();

        $fee = Fee::forceCreate($this->formatParams($request));

        $this->updateRecords($request, $fee);

        \DB::commit();

        return $fee;
    }

    private function formatParams(Request $request, ?Fee $fee = null): array
    {
        $formatted = [
            'name' => $request->name,
            'description' => $request->description,
        ];

        if (! $fee) {
            $formatted['period_id'] = auth()->user()->current_period_id;
        }

        return $formatted;
    }

    private function updateRecords(Request $request, Fee $fee): void
    {
        $assignedTransportCircleIds = StudentFee::query()
            ->whereIn('transport_circle_id', $fee->records->pluck('transport_circle_id')->all())
            ->pluck('transport_circle_id')
            ->all();

        $circleIds = [];
        foreach ($request->records as $record) {
            $circleIds[] = Arr::get($record, 'circle.id');

            if (in_array(Arr::get($record, 'circle.id'), $assignedTransportCircleIds)) {
                continue;
            }

            $feeRecord = FeeRecord::firstOrCreate([
                'transport_fee_id' => $fee->id,
                'transport_circle_id' => Arr::get($record, 'circle.id'),
            ]);

            $feeRecord->arrival_amount = Arr::get($record, 'arrival_amount', 0);
            $feeRecord->departure_amount = Arr::get($record, 'departure_amount', 0);
            $feeRecord->roundtrip_amount = Arr::get($record, 'roundtrip_amount', 0);
            $feeRecord->save();
        }

        FeeRecord::query()
            ->whereTransportFeeId($fee->id)
            ->whereNotIn('transport_circle_id', $circleIds)
            ->delete();
    }

    public function isAssigned(Fee $fee): bool
    {
        $circleIds = $fee->records->pluck('transport_circle_id')->all();

        if (FeeInstallment::whereTransportFeeId($fee->id)->exists()) {
            return true;
        }

        if (StudentFee::whereIn('transport_circle_id', $circleIds)->count()) {
            return true;
        }

        return false;
    }

    private function ensureNotAssigned(Fee $fee): void
    {
        if (! $this->isAssigned($fee)) {
            return;
        }

        throw ValidationException::withMessages(['message' => trans('global.associated_with_dependency', ['attribute' => trans('transport.fee.fee'), 'dependency' => trans('finance.fee_structure.fee_structure')])]);
    }

    public function update(Request $request, Fee $fee): void
    {
        // $this->ensureNotAssigned($fee);

        \DB::beginTransaction();

        $fee->forceFill($this->formatParams($request, $fee))->save();

        $this->updateRecords($request, $fee);

        \DB::commit();
    }

    public function deletable(Fee $fee, $validate = false): ?bool
    {
        $this->ensureNotAssigned($fee);

        return true;
    }
}
