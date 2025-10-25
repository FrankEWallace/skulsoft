<?php

namespace App\Http\Resources\Blog;

use App\Enums\Blog\Status;
use App\Enums\Blog\Visibility;
use App\Http\Resources\MediaResource;
use App\Http\Resources\OptionResource;
use App\Http\Resources\TagResource;
use App\Support\MarkdownParser;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class BlogResource extends JsonResource
{
    use MarkdownParser;

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
            'sub_title' => $this->sub_title,
            'slug' => $this->slug,
            'category' => OptionResource::make($this->whenLoaded('category')),
            'content' => $this->content,
            'content_html' => $this->parse($this->content),
            'assets' => [
                'cover' => $this->cover_image,
                'default_cover' => ! Arr::get($this->assets, 'cover') ? true : false,
                'og' => $this->og_image,
                'default_og' => ! Arr::get($this->assets, 'og') ? true : false,
            ],
            'seo' => [
                'robots' => (bool) Arr::get($this->seo, 'robots'),
                'meta_title' => Arr::get($this->seo, 'meta_title'),
                'meta_description' => Arr::get($this->seo, 'meta_description'),
                'meta_keywords' => Arr::get($this->seo, 'meta_keywords'),
            ],
            'archived_at' => $this->archived_at,
            'pinned_at' => $this->pinned_at,
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'tag_summary' => $this->showTags(),
            'status' => Status::getDetail($this->status),
            'visibility' => Visibility::getDetail($this->visibility),
            'is_published' => $this->is_published,
            'published_at' => $this->published_at,
            'media_token' => $this->getMeta('media_token'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
