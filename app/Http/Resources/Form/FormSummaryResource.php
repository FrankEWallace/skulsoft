<?php

namespace App\Http\Resources\Form;

use App\Enums\Form\Status;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class FormSummaryResource extends JsonResource
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
            'name' => $this->name,
            'due_date' => $this->due_date,
            'published_at' => $this->published_at,
            'summary' => $this->summary,
            'excerpt' => Str::summary($this->summary),
            'description' => $this->description,
            'status' => Status::getDetail($this->status),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
