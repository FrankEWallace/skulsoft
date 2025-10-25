<?php

namespace App\Http\Resources\Reception;

use App\Enums\Reception\VisitorType;
use App\Http\Resources\Employee\EmployeeBasicResource;
use App\Http\Resources\MediaResource;
use App\Http\Resources\OptionResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class VisitorLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $name = $this->name;
        $contactNumber = $this->contact_number;

        if ($this->type != 'other') {
            $name = $this->visitor?->contact->name;
            $contactNumber = $this->visitor?->contact->contact_number;
        }

        return [
            'uuid' => $this->uuid,
            'code_number' => $this->code_number,
            'type' => VisitorType::getDetail($this->type),
            'purpose' => OptionResource::make($this->whenLoaded('purpose')),
            'entry_at' => $this->entry_at,
            'exit_at' => $this->exit_at,
            'name' => $name,
            'company_name' => Arr::get($this->company, 'name'),
            'contact_number' => $contactNumber,
            'count' => $this->count,
            'visitor' => [
                'uuid' => $this->visitor?->uuid,
                'name' => $this->visitor?->contact->name,
                'contact_number' => $this->visitor?->contact->contact_number,
            ],
            'image' => collect($this->getMeta('images', []))->map(function ($image) {
                return url('/storage/'.$image);
            })->first(),
            'images' => collect($this->getMeta('images', []))->map(function ($image) {
                return [
                    'id' => uniqid(),
                    'path' => $image,
                    'url' => url('/storage/'.$image),
                ];
            }),
            'employee' => EmployeeBasicResource::make($this->whenLoaded('employee')),
            'remarks' => $this->remarks,
            'media_token' => $this->getMeta('media_token'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
