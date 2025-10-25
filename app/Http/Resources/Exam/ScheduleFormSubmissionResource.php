<?php

namespace App\Http\Resources\Exam;

use App\Enums\Exam\AssessmentAttempt;
use App\Http\Resources\Academic\BatchResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class ScheduleFormSubmissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        if ($this->is_reassessment) {
            $records = $this->reassessment_subjects;
        } else {
            $records = $this->available_subjects;
        }

        return [
            'uuid' => $this->uuid,
            'exam' => ExamResource::make($this->whenLoaded('exam')),
            'batch' => BatchResource::make($this->whenLoaded('batch')),
            'is_reassessment' => $this->is_reassessment,
            'attempt' => AssessmentAttempt::getDetail($this->attempt),
            'grade' => GradeResource::make($this->whenLoaded('grade')),
            'assessment' => AssessmentResource::make($this->whenLoaded('assessment')),
            'observation' => ObservationResource::make($this->whenLoaded('observation')),
            'payable_fee' => \Price::from($this->payable_fee),
            'has_form' => $this->has_form,
            $this->mergeWhen($this->has_form, [
                'form_uuid' => $this->form_uuid,
                'confirmed_at' => \Cal::dateTime($this->confirmed_at),
                'submitted_at' => \Cal::dateTime($this->submitted_at),
                'approved_at' => \Cal::dateTime($this->approved_at),
            ]),
            $this->mergeWhen($this->is_reassessment && $this->has_form, [
                'reassessment_subjects' => $this->reassessment_subjects,
            ]),
            'records' => $this->getRecords($records),
            'start_date' => \Cal::date($this->start_date),
            'end_date' => \Cal::date($this->end_date),
            'marksheet_status' => $this->marksheet_status,
            'publish_admit_card' => (bool) $this->getMeta('publish_admit_card'),
            'description' => $this->description,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }

    private function getRecords($records)
    {
        if (! $this->relationLoaded('records')) {
            return $records;
        }

        $examAssessmentRecords = collect($this->assessment->records ?? []);

        return collect($records)->map(function ($record) use ($examAssessmentRecords) {
            $scheduleRecord = $this->records->firstWhere('uuid', $record['uuid']);

            $assessments = collect(Arr::get($scheduleRecord->config, 'assessments', []))
                ->map(function ($assessment) use ($examAssessmentRecords) {
                    $examAssessmentRecord = $examAssessmentRecords
                        ->firstWhere('code', $assessment['code']);

                    $maxMark = $assessment['max_mark'];
                    $passingMark = $assessment['passing_mark'] ?? '';

                    $marks = $maxMark;
                    if (! empty($passingMark)) {
                        $marks .= '/'.$passingMark;
                    }

                    return [
                        'name' => $examAssessmentRecord['name'],
                        'code' => $assessment['code'],
                        'max_mark' => $maxMark,
                        'passing_mark' => $passingMark,
                        'marks' => $marks,
                    ];
                });

            return [
                ...$record,
                'assessments' => $assessments,
            ];
        });
    }
}
