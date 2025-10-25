<?php

namespace App\Http\Resources\Exam;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class GradeResource extends JsonResource
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
                    'code' => Arr::get($record, 'code'),
                    'value' => Arr::get($record, 'value'),
                    'label' => Arr::get($record, 'label'),
                    'is_fail_grade' => (bool) Arr::get($record, 'is_fail_grade'),
                    'min_score' => Arr::get($record, 'min_score'),
                    'max_score' => Arr::get($record, 'max_score'),
                    'description' => Arr::get($record, 'description'),
                ];
            }),
            'description' => $this->description,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
