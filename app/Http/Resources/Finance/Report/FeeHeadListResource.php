<?php

namespace App\Http\Resources\Finance\Report;

use App\Enums\Gender;
use Illuminate\Http\Resources\Json\JsonResource;

class FeeHeadListResource extends JsonResource
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
            'name' => $this->name,
            'father_name' => $this->father_name,
            'code_number' => $this->code_number,
            'joining_date' => \Cal::date($this->joining_date),
            'batch_name' => $this->batch_name,
            'course_name' => $this->course_name,
            'gender' => Gender::getDetail($this->gender),
            'birth_date' => \Cal::date($this->birth_date),
            'contact_number' => $this->contact_number,
            'total' => \Price::from($this->total_amount),
            'concession' => \Price::from($this->concession_amount),
            'paid' => \Price::from($this->paid_amount),
            'balance' => \Price::from($this->balance_amount),
        ];
    }
}
