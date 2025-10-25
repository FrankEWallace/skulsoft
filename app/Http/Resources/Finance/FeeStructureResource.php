<?php

namespace App\Http\Resources\Finance;

use App\Http\Resources\Academic\PeriodResource;
use App\Models\Finance\FeeGroup;
use Illuminate\Http\Resources\Json\JsonResource;

class FeeStructureResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'period' => PeriodResource::make($this->whenLoaded('period')),
            'allocations' => FeeAllocationResource::collection($this->whenLoaded('allocations')),
            'fee_groups' => $this->getFeeGroups(),
            'is_editable' => $this->isEditable(),
            'assigned_students' => $this->assigned_students,
            'description' => $this->description,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }

    private function getFeeGroups(): array
    {
        if (! $this->relationLoaded('installments')) {
            return [];
        }

        $feeGroups = [];
        foreach (FeeGroup::with('heads')->byPeriod()->get() as $feeGroup) {
            if ($feeGroup->getMeta('is_custom')) {
                continue;
            }

            $feeInstallments = $this->installments->where('fee_group_id', $feeGroup->id);

            $total = 0;
            $total += $feeInstallments->sum(function ($feeInstallment) {
                return $feeInstallment->records->sum('amount.value');
            });

            $feeGroups[] = [
                'uuid' => $feeGroup->uuid,
                'name' => $feeGroup->name,
                'heads' => FeeHeadResource::collection($feeGroup->heads),
                'installments' => FeeInstallmentResource::collection($feeInstallments),
                'total' => $total,
                'is_custom' => (bool) $feeGroup->getMeta('is_custom'),
            ];
        }

        return $feeGroups;
    }

    private function isEditable(): ?bool
    {
        return $this->assigned_students ? false : true;
    }
}
