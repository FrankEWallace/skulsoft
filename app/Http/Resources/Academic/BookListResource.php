<?php

namespace App\Http\Resources\Academic;

use App\Enums\Academic\BookListType;
use Illuminate\Http\Resources\Json\JsonResource;

class BookListResource extends JsonResource
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
            'course' => CourseResource::make($this->whenLoaded('course')),
            'subject' => SubjectResource::make($this->whenLoaded('subject')),
            'type' => BookListType::getDetail($this->type),
            'title' => $this->title,
            'author' => $this->type != BookListType::NOTEBOOK ? $this->author : null,
            'publisher' => $this->type != BookListType::NOTEBOOK ? $this->publisher : null,
            'quantity' => $this->type == BookListType::NOTEBOOK ? $this->quantity : null,
            'pages' => $this->type == BookListType::NOTEBOOK ? $this->pages : null,
            'description' => $this->description,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
