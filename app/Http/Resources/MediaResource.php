<?php

namespace App\Http\Resources;

use App\Helpers\SysHelper;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class MediaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $fileSize = SysHelper::fileSize($this->getMeta('size'));

        return [
            'uuid' => $this->uuid,
            'file' => [
                'name' => $this->file_name,
                'size' => $fileSize,
            ],
            'name' => $this->file_name,
            'status' => $this->status ? 'uploaded' : 'waiting',
            'mime' => $this->getMeta('mime'),
            'mime_type' => $this->getMeta('mime'),
            'is_previewable' => $this->isPreviewable(),
            'is_image' => Str::startsWith($this->getMeta('mime'), 'image/'),
            'icon' => $this->getIcon(),
            'url' => url('/'),
            'size' => $fileSize,
            'user' => UserSummaryForGuestResource::make($this->whenLoaded('user')),
            'section_name' => Str::title($this->getMeta('section')),
            'section' => $this->getMeta('section'),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }

    private function isPreviewable(): bool
    {
        return in_array($this->getMeta('mime'), ['application/pdf', 'image/jpg', 'image/jpeg', 'image/png']);
    }
}
