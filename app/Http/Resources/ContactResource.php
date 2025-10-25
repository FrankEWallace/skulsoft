<?php

namespace App\Http\Resources;

use App\Enums\BloodGroup;
use App\Enums\ContactSource;
use App\Enums\Gender;
use App\Enums\Locality;
use App\Enums\MaritalStatus;
use App\Http\Resources\Student\DocumentResource;
use App\Http\Resources\Student\QualificationResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class ContactResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $address = $this->address;

        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'third_name' => $this->third_name,
            'last_name' => $this->last_name,
            'father_name' => $this->father_name,
            'father_contact_number' => $this->getMeta('father_contact_number'),
            'father_email' => $this->getMeta('father_email'),
            'mother_name' => $this->mother_name,
            'mother_contact_number' => $this->getMeta('mother_contact_number'),
            'mother_email' => $this->getMeta('mother_email'),
            'birth_date' => $this->birth_date,
            'anniversary_date' => $this->anniversary_date,
            'contact_number' => $this->contact_number,
            'unique_id_number1' => $this->unique_id_number1,
            'unique_id_number2' => $this->unique_id_number2,
            'unique_id_number3' => $this->unique_id_number3,
            'unique_id_number4' => $this->unique_id_number4,
            'unique_id_number5' => $this->unique_id_number5,
            'type' => $this->type,
            'has_user' => $this->user_id ? true : false,
            'user' => UserSummaryResource::make($this->whenLoaded('user')),
            'religion' => OptionResource::make($this->whenLoaded('religion')),
            'caste' => OptionResource::make($this->whenLoaded('caste')),
            'category' => OptionResource::make($this->whenLoaded('category')),
            'guardian' => GuardianResource::make($this->guardian),
            'guardians' => GuardianResource::collection($this->whenLoaded('guardians')),
            'qualifications' => QualificationResource::collection($this->whenLoaded('qualifications')),
            'documents' => DocumentResource::collection($this->whenLoaded('documents')),
            'email' => $this->email,
            'gender' => Gender::getDetail($this->gender),
            'blood_group' => BloodGroup::getDetail($this->blood_group),
            'locality' => Locality::getDetail($this->locality),
            'marital_status' => MaritalStatus::getDetail($this->marital_status),
            'photo' => $this->photo_url,
            'photo_url' => url($this->photo_url),
            'nationality' => $this->nationality,
            'mother_tongue' => $this->mother_tongue,
            'birth_place' => $this->birth_place,
            'occupation' => $this->occupation,
            'annual_income' => $this->annual_income,
            'organization_name' => $this->getMeta('organization_name'),
            'designation' => $this->getMeta('designation'),
            'alternate_records' => [
                'contact_number' => Arr::get($this->alternate_records, 'contact_number'),
                'email' => Arr::get($this->alternate_records, 'email'),
            ],
            'address' => Arr::toAddress([
                'address_line1' => Arr::get($address, 'present.address_line1'),
                'address_line2' => Arr::get($address, 'present.address_line2'),
                'city' => Arr::get($address, 'present.city'),
                'state' => Arr::get($address, 'present.state'),
                'zipcode' => Arr::get($address, 'present.zipcode'),
                'country' => Arr::get($address, 'present.country'),
            ]),
            'present_address' => $this->present_address,
            'present_address_display' => Arr::toAddress($this->present_address),
            'same_as_present_address' => $this->same_as_present_address,
            'permanent_address' => $this->permanent_address,
            'permanent_address_display' => Arr::toAddress($this->permanent_address),
            'source' => ContactSource::getDetail($this->source),
            $this->mergeWhen($request->has_custom_fields, [
                'custom_fields' => $this->getCustomFieldsValues($request->custom_field_form),
            ]),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
