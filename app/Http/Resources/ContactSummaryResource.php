<?php

namespace App\Http\Resources;

use App\Enums\ContactSource;
use App\Enums\Gender;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactSummaryResource extends JsonResource
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
            'birth_date' => $this->birth_date,
            'anniversary_date' => $this->anniversary_date,
            'contact_number' => $this->contact_number,
            'email' => $this->email,
            'gender' => Gender::getDetail($this->gender),
            'photo' => $this->photo_url,
            'photo_url' => url($this->photo_url),
            'source' => ContactSource::getDetail($this->source),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
