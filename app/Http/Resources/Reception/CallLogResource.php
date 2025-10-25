<?php

namespace App\Http\Resources\Reception;

use App\Enums\Reception\CallType;
use App\Http\Resources\MediaResource;
use App\Http\Resources\OptionResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class CallLogResource extends JsonResource
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
            'type' => CallType::getDetail($this->type),
            'purpose' => OptionResource::make($this->whenLoaded('purpose')),
            'call_at' => $this->call_at,
            'incoming_number' => $this->incoming_number,
            'outgoing_number' => $this->outgoing_number,
            'duration' => [
                'value' => $this->duration,
                'label' => $this->duration.' ('.trans('list.durations.m').')',
            ],
            'name' => $this->name,
            'company_name' => Arr::get($this->company, 'name'),
            'conversation' => $this->conversation,
            'remarks' => $this->remarks,
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
