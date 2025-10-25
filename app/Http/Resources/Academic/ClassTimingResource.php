<?php

namespace App\Http\Resources\Academic;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassTimingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $startTime = $this->sessions->min('start_time');
        $endTime = $this->sessions->max('end_time');

        $duration = Carbon::parse($startTime->value)->diff(Carbon::parse($endTime->value));

        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration' => $duration->h.' '.trans('list.durations.hours').' '.$duration->i.' '.trans('list.durations.minutes'),
            'period' => $startTime->formatted.' - '.$endTime->formatted,
            'sessions' => ClassTimingSessionResource::collection($this->whenLoaded('sessions')),
            'session_count' => $this->sessions->where('is_break', false)->count().' '.trans('academic.class_timing.session'),
            'break_count' => $this->sessions->where('is_break', true)->count().' '.trans('academic.class_timing.break'),
            'description' => $this->description,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
