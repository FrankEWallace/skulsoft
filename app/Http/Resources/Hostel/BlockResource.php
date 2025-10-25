<?php

namespace App\Http\Resources\Hostel;

use App\Http\Resources\TeamResource;
use Illuminate\Http\Resources\Json\JsonResource;

class BlockResource extends JsonResource
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
            'name' => $this->name,
            'alias' => $this->alias,
            'team' => TeamResource::make($this->whenLoaded('team')),
            'contact_number' => $this->getMeta('hostel.contact_number'),
            'contact_email' => $this->getMeta('hostel.contact_email'),
            'address' => nl2br($this->getMeta('hostel.address')),
            $this->mergeWhen($request->query('details'), [
                'incharge' => BlockInchargeResource::make($this->whenLoaded('incharge')),
                'incharges' => BlockInchargeResource::collection($this->whenLoaded('incharges')),
            ]),
            'description' => $this->description,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
