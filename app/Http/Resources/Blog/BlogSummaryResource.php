<?php

namespace App\Http\Resources\Blog;

use App\Enums\Blog\Status;
use App\Enums\Blog\Visibility;
use App\Http\Resources\OptionResource;
use App\Http\Resources\TagResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class BlogSummaryResource extends JsonResource
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
            'sub_title' => $this->sub_title,
            'sub_title_excerpt' => Str::summary($this->sub_title, 50),
            'slug' => $this->slug,
            'url' => $this->url,
            'category' => OptionResource::make($this->whenLoaded('category')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'tag_summary' => $this->showTags(),
            'archived_at' => $this->archived_at,
            'pinned_at' => $this->pinned_at,
            'status' => Status::getDetail($this->status),
            'visibility' => Visibility::getDetail($this->visibility),
            'is_published' => $this->is_published,
            'published_at' => $this->published_at,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
