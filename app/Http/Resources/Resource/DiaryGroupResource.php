<?php

namespace App\Http\Resources\Resource;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class DiaryGroupResource extends JsonResource
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
            'uuid' => (string) Str::uuid(),
            'batch_uuid' => $this->batch_uuid,
            'course_batch' => $this->course_batch,
            'date' => $this->date,
        ];
    }
}
