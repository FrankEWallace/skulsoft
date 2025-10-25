<?php

namespace App\Http\Resources\Mess;

use Illuminate\Http\Resources\Json\JsonResource;

class MealLogResource extends JsonResource
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
            'date' => $this->date,
            'meal' => MealResource::make($this->whenLoaded('meal')),
            'menuItems' => $this->records->map(function ($record) {
                return ['item' => $record->item->name];
            })->pluck('item')->implode(', '),
            'records' => MealLogRecordResource::collection($this->whenLoaded('records')),
            'description' => $this->description,
            'remarks' => $this->remarks,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
