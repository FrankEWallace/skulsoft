<?php

namespace App\Http\Resources\Reception;

use App\Enums\Reception\CorrespondenceMode;
use App\Enums\Reception\CorrespondenceType;
use App\Http\Resources\MediaResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class CorrespondenceResource extends JsonResource
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
            'type' => CorrespondenceType::getDetail($this->type),
            'mode' => CorrespondenceMode::getDetail($this->mode),
            'reference' => self::make($this->whenLoaded('reference')),
            'date' => $this->date,
            'letter_number' => $this->letter_number,
            'sender_title' => Arr::get($this->sender, 'title'),
            'sender_address' => Arr::get($this->sender, 'address'),
            'receiver_title' => Arr::get($this->receiver, 'title'),
            'receiver_address' => Arr::get($this->receiver, 'address'),
            'remarks' => $this->remarks,
            'media_token' => $this->getMeta('media_token'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
