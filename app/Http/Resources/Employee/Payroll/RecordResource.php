<?php

namespace App\Http\Resources\Employee\Payroll;

use App\Enums\Employee\Payroll\PayHeadCategory;
use Illuminate\Http\Resources\Json\JsonResource;

class RecordResource extends JsonResource
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
            'pay_head' => [
                'uuid' => $this->payHead->uuid,
                'name' => $this->payHead->name,
                'alias' => $this->payHead->alias,
                'category' => PayHeadCategory::getDetail($this->payHead->category),
                'code' => $this->payHead->code,
                'as_total' => $this->as_total,
                'visibility' => $this->visibility,
            ],
            'calculated' => $this->calculated,
            'amount' => $this->amount,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
