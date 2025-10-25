<?php

namespace App\Http\Resources\Discipline;

use App\Enums\Discipline\IncidentNature;
use App\Enums\Discipline\IncidentSeverity;
use App\Http\Resources\MediaResource;
use App\Http\Resources\OptionResource;
use Illuminate\Http\Resources\Json\JsonResource;

class IncidentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $name = $this->model?->contact->name;
        $contactNumber = $this->model?->contact->contact_number;

        return [
            'uuid' => $this->uuid,
            'title' => $this->title,
            'student' => $this->model?->uuid,
            'name' => $name,
            'contact_number' => $contactNumber,
            'category' => OptionResource::make($this->whenLoaded('category')),
            'nature' => IncidentNature::getDetail($this->nature),
            'severity' => IncidentSeverity::getDetail($this->severity),
            'reported_by' => $this->reported_by,
            'date' => $this->date,
            'description' => $this->description,
            'action' => $this->action,
            'remarks' => $this->remarks,
            'media_token' => $this->getMeta('media_token'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
