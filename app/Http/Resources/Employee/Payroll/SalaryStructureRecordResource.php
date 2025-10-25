<?php

namespace App\Http\Resources\Employee\Payroll;

use Illuminate\Http\Resources\Json\JsonResource;

class SalaryStructureRecordResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'uuid' => $this->uuid,
            'unit' => $this->unit,
            'amount' => $this->amount,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
