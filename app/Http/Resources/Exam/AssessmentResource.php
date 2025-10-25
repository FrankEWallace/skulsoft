<?php

namespace App\Http\Resources\Exam;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AssessmentResource extends JsonResource
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
            'records' => collect($this->records)->map(function ($record) {
                return [
                    'uuid' => (string) Str::uuid(),
                    'position' => Arr::get($record, 'position', 0),
                    'name' => Arr::get($record, 'name'),
                    'code' => Arr::get($record, 'code'),
                    'max_mark' => Arr::get($record, 'max_mark'),
                    'passing_mark' => Arr::get($record, 'passing_mark'),
                    'description' => Arr::get($record, 'description'),
                ];
            }),
            'description' => $this->description,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
