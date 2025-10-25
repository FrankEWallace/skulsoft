<?php

namespace App\Http\Resources\Employee;

use App\Http\Resources\MediaResource;
use App\Http\Resources\OptionResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class DialogueResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $selfUpload = (bool) $this->getMeta('self_upload');

        return [
            'uuid' => $this->uuid,
            'employee' => EmployeeSummaryResource::make($this->whenLoaded('model')),
            'category' => OptionResource::make($this->whenLoaded('category')),
            'title' => $this->title,
            'description' => $this->description,
            'date' => $this->date,
            'is_editable' => (bool) $this->is_editable,
            'user' => UserResource::make($this->whenLoaded('user')),
            'media_token' => $this->getMeta('media_token'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
