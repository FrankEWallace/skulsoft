<?php

namespace App\Http\Resources\Academic;

use App\Enums\OptionType;
use App\Http\Resources\OptionResource;
use App\Models\Option;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class CourseResource extends JsonResource
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
            'term' => $this->term,
            'name_with_term' => $this->name_with_term,
            'name_with_term_and_division' => $this->name_with_term_and_division,
            'code' => $this->code,
            'shortcode' => $this->shortcode,
            'division' => DivisionResource::make($this->whenLoaded('division')),
            'subject_records' => SubjectRecordResource::collection($this->whenLoaded('subjectRecords')),
            'batches' => BatchResource::collection($this->whenLoaded('batches')),
            'enrollment_seats' => $this->getEnrollmentSeats(),
            $this->mergeWhen($request->query('details'), [
                'incharge' => CourseInchargeResource::make($this->whenLoaded('incharge')),
                'incharges' => CourseInchargeResource::collection($this->whenLoaded('incharges')),
                'period_history' => collect($this->getMeta('period_history', []))->map(function ($period) {
                    return [
                        'name' => Arr::get($period, 'name'),
                        'datetime' => \Cal::dateTime(Arr::get($period, 'datetime')),
                    ];
                }),
            ]),
            'enable_registration' => $this->enable_registration,
            'registration_fee' => $this->registration_fee,
            'position' => $this->position,
            'pg_account' => $this->getMeta('pg_account'),
            'description' => $this->description,
            'batch_with_same_subject' => (bool) $this->getConfig('batch_with_same_subject'),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }

    public function getEnrollmentSeats()
    {
        if (! $this->relationLoaded('enrollmentSeats')) {
            return [];
        }

        $enrollmentTypes = Option::query()
            ->byTeam()
            ->where('type', OptionType::STUDENT_ENROLLMENT_TYPE)
            ->get();

        return $enrollmentTypes->map(function ($enrollmentType) {
            $enrollmentSeat = $this->enrollmentSeats->firstWhere('enrollment_type_id', $enrollmentType->id);

            return [
                'enrollment_type' => OptionResource::make($enrollmentType),
                'position' => $enrollmentSeat?->position ?? 0,
                'max_seat' => $enrollmentSeat?->max_seat ?? 0,
                'booked_seat' => $enrollmentSeat?->booked_seat ?? 0,
            ];
        });
    }
}
