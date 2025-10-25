<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Resources\Json\JsonResource;

class CertificateResource extends JsonResource
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
            'date' => $this->date,
            'template' => CertificateTemplateSummaryResource::make($this->whenLoaded('template')),
            'to' => [
                'uuid' => $this->model?->uuid,
                'name' => $this->model_type ? $this->model?->contact->name : $this->getMeta('name'),
                'contact_number' => $this->model?->contact->contact_number,
            ],
            'is_anonymous' => empty($this->model_type) ? true : false,
            'is_duplicate' => $this->is_duplicate,
            'custom_fields' => collect($this->custom_fields)->map(function ($value, $key) {
                return [
                    'key' => $key,
                    'value' => $value,
                ];
            })->values()->all(),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
