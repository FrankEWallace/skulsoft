<?php

namespace App\Http\Resources\Site;

use App\Concerns\HasStorage;
use App\Support\MarkdownParser;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class BlockResource extends JsonResource
{
    use HasStorage, MarkdownParser;

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
            'is_slider' => $this->is_slider,
            'title' => $this->title,
            'sub_title' => $this->sub_title,
            'content' => $this->content,
            'content_html' => $this->parse($this->content),
            'menu' => MenuResource::make($this->whenLoaded('menu')),
            'url' => $this->getMeta('url'),
            'assets' => [
                'cover' => $this->cover_image,
                'default_cover' => ! Arr::get($this->assets, 'cover') ? true : false,
            ],
            $this->mergeWhen($this->is_slider, [
                'slider_images' => collect($this->slider_images)->map(function ($item) {
                    $item['url'] = $this->getImageFile(visibility: 'public', path: Arr::get($item, 'path'), default: '/images/site/cover.webp');

                    return $item;
                })->toArray(),
                'default_slider_image' => $this->default_slider_image,
            ]),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
