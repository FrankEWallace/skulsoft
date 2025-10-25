<?php

namespace App\Http\Resources;

use App\Enums\FamilyRelation;
use App\Enums\Gender;
use App\Http\Resources\Student\StudentResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class GuardianListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $address = json_decode($this->address, true);

        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'student_name' => $this->student_name,
            'relation' => FamilyRelation::getDetail($this->relation),
            'gender' => Gender::getDetail($this->gender),
            'birth_date' => \Cal::date($this->birth_date),
            'contact_number' => $this->contact_number,
            'email' => $this->email,
            'student' => StudentResource::make($this->whenLoaded('student')),
            'address' => Arr::toAddress([
                'address_line1' => Arr::get($address, 'present.address_line1'),
                'address_line2' => Arr::get($address, 'present.address_line2'),
                'city' => Arr::get($address, 'present.city'),
                'state' => Arr::get($address, 'present.state'),
                'zipcode' => Arr::get($address, 'present.zipcode'),
                'country' => Arr::get($address, 'present.country'),
            ]),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
