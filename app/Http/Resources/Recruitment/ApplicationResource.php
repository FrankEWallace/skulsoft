<?php

namespace App\Http\Resources\Recruitment;

use App\Http\Resources\ContactResource;
use App\Http\Resources\Employee\DesignationResource;
use App\Http\Resources\MediaResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
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
            'contact' => ContactResource::make($this->whenLoaded('contact')),
            'vacancy' => VacancyResource::make($this->whenLoaded('vacancy')),
            'designation' => DesignationResource::make($this->whenLoaded('designation')),
            'is_manual' => $this->getMeta('is_manual'),
            'application_date' => $this->application_date,
            'availability_date' => $this->availability_date,
            'qualification_summary' => $this->qualification_summary,
            'cover_letter' => $this->cover_letter,
            'is_editable' => $this->isEditable(),
            'media_token' => $this->getMeta('media_token'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
