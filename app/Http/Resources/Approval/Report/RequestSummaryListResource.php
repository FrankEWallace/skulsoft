<?php

namespace App\Http\Resources\Approval\Report;

use Illuminate\Http\Resources\Json\JsonResource;

class RequestSummaryListResource extends JsonResource
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
        ];
    }
}
