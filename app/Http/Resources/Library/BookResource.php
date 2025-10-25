<?php

namespace App\Http\Resources\Library;

use App\Http\Resources\OptionResource;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
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
            'title' => $this->title,
            'copies_count' => $this->copies_count,
            'available_copies_count' => $this->available_copies_count,
            'author' => OptionResource::make($this->whenLoaded('author')),
            'publisher' => OptionResource::make($this->whenLoaded('publisher')),
            'language' => OptionResource::make($this->whenLoaded('language')),
            'topic' => OptionResource::make($this->whenLoaded('topic')),
            'category' => OptionResource::make($this->whenLoaded('category')),
            'copies' => BookCopyResource::collection($this->whenLoaded('copies')),
            'sub_title' => $this->sub_title,
            'subject' => $this->subject,
            'year_published' => $this->year_published,
            'volume' => $this->volume,
            'isbn_number' => $this->isbn_number,
            'call_number' => $this->call_number,
            'edition' => $this->edition,
            'type' => $this->type,
            'page' => $this->page,
            'price' => $this->price,
            'summary' => $this->summary,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
