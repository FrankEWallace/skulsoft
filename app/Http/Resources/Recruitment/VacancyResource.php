<?php

namespace App\Http\Resources\Recruitment;

use App\Http\Resources\MediaResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class VacancyResource extends JsonResource
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
            'code_number' => $this->code_number,
            'title' => $this->title,
            'title_excerpt' => Str::summary($this->title, 100),
            'records' => VacancyRecordResource::collection($this->whenLoaded('records')),
            'last_application_date' => $this->last_application_date,
            'published_at' => $this->published_at,
            'description' => $this->description,
            'responsibility' => $this->responsibility,
            'media_token' => $this->getMeta('media_token'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
