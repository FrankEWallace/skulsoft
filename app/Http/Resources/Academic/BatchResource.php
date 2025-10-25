<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class BatchResource extends JsonResource
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
            $this->mergeWhen($request->query('details'), [
                'incharge' => BatchInchargeResource::make($this->whenLoaded('incharge')),
                'incharges' => DivisionInchargeResource::collection($this->whenLoaded('incharges')),
                'period_history' => collect($this->getMeta('period_history', []))->map(function ($period) {
                    return [
                        'name' => Arr::get($period, 'name'),
                        'datetime' => \Cal::dateTime(Arr::get($period, 'datetime')),
                    ];
                }),
            ]),
            'max_strength' => $this->max_strength,
            'current_strength' => $this->current_strength,
            $this->mergeWhen($request->query('with_subjects'), [
                'subject_records' => SubjectRecordResource::collection($this->whenLoaded('subjectRecords')),
            ]),
            'subject_records' => SubjectRecordResource::collection($this->whenLoaded('subjectRecords')),
            'roll_number_prefix' => Arr::get($this->config, 'roll_number_prefix'),
            'course' => CourseResource::make($this->whenLoaded('course')),
            'position' => $this->position,
            'pg_account' => $this->getMeta('pg_account'),
            'description' => $this->description,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
