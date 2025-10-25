<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class ActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $activity = Arr::get($this->description, 'activity', []);
        $attributes = Arr::get($this->description, 'attributes', []);

        $attributes['causer'] = $this->user->name;

        return [
            'uuid' => $this->uuid,
            'description' => trans($activity, $attributes),
            $this->mergeWhen(auth()->check(), [
                'user' => UserSummaryResource::make($this->whenLoaded('user')),
            ], [
                'user' => UserSummaryForGuestResource::make($this->whenLoaded('user')),
            ]),
            'event' => $this->event,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
