<?php

namespace App\Http\Resources\Reception;

use App\Enums\Reception\GatePassTo;
use App\Http\Resources\AudienceResource;
use App\Http\Resources\MediaResource;
use App\Http\Resources\OptionResource;
use Illuminate\Http\Resources\Json\JsonResource;

class GatePassResource extends JsonResource
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
            'code_number' => $this->code_number,
            'requester_type' => GatePassTo::getDetail($this->requester_type),
            'audiences' => AudienceResource::collection($this->whenLoaded('audiences')),
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'left_at' => $this->left_at,
            'returned_at' => $this->returned_at,
            'purpose' => OptionResource::make($this->whenLoaded('purpose')),
            'reason' => $this->reason,
            'remarks' => $this->remarks,
            'image' => collect($this->getMeta('images', []))->map(function ($image) {
                return url('/storage/'.$image);
            })->first(),
            'images' => collect($this->getMeta('images', []))->map(function ($image) {
                return [
                    'id' => uniqid(),
                    'path' => $image,
                    'url' => url('/storage/'.$image),
                ];
            }),
            'media_token' => $this->getMeta('media_token'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
