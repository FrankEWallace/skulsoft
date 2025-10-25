<?php

namespace App\Http\Resources\Transport;

use App\Enums\Transport\Direction;
use App\Http\Resources\Academic\PeriodResource;
use App\Http\Resources\Transport\Vehicle\VehicleResource;
use Illuminate\Http\Resources\Json\JsonResource;

class RouteListResource extends JsonResource
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
            'name' => $this->name,
            'max_capacity' => $this->max_capacity,
            'vehicle' => VehicleResource::make($this->whenLoaded('vehicle')),
            'period' => PeriodResource::make($this->whenLoaded('period')),
            'route_stoppages_count' => $this->route_stoppages_count,
            'route_passengers_count' => $this->route_passengers_count,
            'direction' => Direction::getDetail($this->direction),
            'arrival_starts_at' => $this->arrival_starts_at,
            'departure_starts_at' => $this->departure_starts_at,
            'description' => $this->description,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
