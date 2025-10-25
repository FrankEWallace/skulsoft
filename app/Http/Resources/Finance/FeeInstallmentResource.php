<?php

namespace App\Http\Resources\Finance;

use App\Enums\Finance\LateFeeFrequency;
use App\Http\Resources\Transport\FeeResource as TransportFeeResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class FeeInstallmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $lateFeeFrequencies = LateFeeFrequency::getOptions();

        $hasLateFee = (bool) Arr::get($this->late_fee, 'applicable', false);
        $lateFeeType = Arr::get($this->late_fee, 'type', 'amount');

        $lateFeeFrequency = LateFeeFrequency::tryFrom(Arr::get($this->late_fee, 'frequency'));

        $lateFeeValue = Arr::get($this->late_fee, 'type', 'amount') == 'amount'
            ? \Price::from(Arr::get($this->late_fee, 'value'))
            : \Percent::from(Arr::get($this->late_fee, 'value'));

        $total = \Price::from($this->records->sum('amount.value'));

        return [
            'uuid' => $this->uuid,
            'is_deletable' => false,
            'title' => $this->title,
            'group' => new FeeGroupResource($this->whenLoaded('group')),
            'due_date' => $this->due_date,
            'has_late_fee' => $hasLateFee,
            'late_fee_frequency' => LateFeeFrequency::getDetail($lateFeeFrequency),
            'late_fee_value' => $lateFeeValue,
            'late_fee_type' => $lateFeeType,
            'late_fee_display' => $this->when($hasLateFee, $lateFeeValue->formatted.' '.LateFeeFrequency::getLabel($lateFeeFrequency?->value)),
            'has_transport_fee' => $this->transport_fee_id ? true : false,
            'transport_fee' => TransportFeeResource::make($this->whenLoaded('transportFee')),
            'total' => $total,
            'heads' => $this->getHeads(),
            'has_no_concession' => (bool) $this->getMeta('has_no_concession'),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }

    private function getHeads()
    {
        $feeHeads = [];
        foreach ($this->group->heads as $feeHead) {
            $feeInstallmentRecord = $this->records->firstWhere('fee_head_id', $feeHead->id);

            $feeHeads[] = [
                'uuid' => $feeHead->uuid,
                'name' => $feeInstallmentRecord?->name ?? $feeHead->name,
                'amount' => $feeInstallmentRecord?->amount,
                'is_optional' => $feeInstallmentRecord?->is_optional ?? false,
                'applicable_to' => $feeInstallmentRecord?->applicable_to ?: 'all',
                'applicable_to_gender' => $feeInstallmentRecord?->applicable_to_gender ?: 'all',
            ];
        }

        return $feeHeads;
    }
}
