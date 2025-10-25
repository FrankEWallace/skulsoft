<?php

namespace App\Http\Resources\Student;

use App\Enums\Gender;
use App\Http\Resources\OptionResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class FeeAllocationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $address = json_decode($this->address, true);

        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'roll_number' => $this->roll_number,
            'contact_number' => $this->contact_number,
            'father_name' => $this->father_name,
            'mother_name' => $this->mother_name,
            'email' => $this->email,
            'fees_count' => $this->fees_count,
            'fee_concession_type' => OptionResource::make($this->whenLoaded('feeConcessionType')),
            'birth_date' => \Cal::date($this->birth_date),
            'gender' => Gender::getDetail($this->gender),
            'code_number' => $this->code_number,
            'joining_date' => \Cal::date($this->joining_date),
            'start_date' => \Cal::date($this->start_date),
            'batch_uuid' => $this->batch_uuid,
            'batch_name' => $this->batch_name,
            'course_uuid' => $this->course_uuid,
            'course_name' => $this->course_name,
            'address' => Arr::toAddress([
                'address_line1' => Arr::get($address, 'present.address_line1'),
                'address_line2' => Arr::get($address, 'present.address_line2'),
                'city' => Arr::get($address, 'present.city'),
                'state' => Arr::get($address, 'present.state'),
                'zipcode' => Arr::get($address, 'present.zipcode'),
                'country' => Arr::get($address, 'present.country'),
            ]),
            'short_address' => Arr::toAddress([
                'address_line1' => Arr::get($address, 'present.address_line1'),
                'address_line2' => Arr::get($address, 'present.address_line2'),
                'city' => Arr::get($address, 'present.city'),
            ]),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
