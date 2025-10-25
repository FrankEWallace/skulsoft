<?php

namespace App\Http\Resources;

use App\Enums\GalleryType;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class GalleryResource extends JsonResource
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
            'title' => $this->title,
            'title_excerpt' => Str::summary($this->title, 50),
            'images_count' => $this->images_count,
            'type' => GalleryType::getDetail($this->type),
            'images' => GalleryImageResource::collection($this->whenLoaded('images')),
            'thumbnail_url' => $this->thumbnail_url,
            'description' => $this->description,
            'date' => $this->date,
            'published_at' => $this->published_at,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
