<?php

namespace App\Http\Resources\Exam;

use App\Enums\Exam\AssessmentAttempt;
use App\Helpers\CalHelper;
use App\Http\Resources\Academic\BatchResource;
use App\Http\Resources\Academic\SubjectResource;
use App\Domain\Academic\Models\SubjectRecord;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class ScheduleResource extends JsonResource
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
            'exam' => ExamResource::make($this->whenLoaded('exam')),
            'exam_config' => $this->when($this->relationLoaded('exam'), [
                'publish_marksheet' => (bool) Arr::get($this->exam->config, $this->attempt->value.'_attempt.publish_marksheet'),
            ]),
            'batch' => BatchResource::make($this->whenLoaded('batch')),
            'is_reassessment' => $this->is_reassessment,
            'attempt' => AssessmentAttempt::getDetail($this->attempt),
            'grade' => GradeResource::make($this->whenLoaded('grade')),
            'assessment' => AssessmentResource::make($this->whenLoaded('assessment')),
            'observation' => ObservationResource::make($this->whenLoaded('observation')),
            $this->mergeWhen($this->relationLoaded('records'), [
                'records' => $this->getRecords(),
            ]),
            $this->mergeWhen($this->has_form && auth()->user()->hasRole('student'), [
                'form_uuid' => $this->form_uuid,
                'confirmed_at' => \Cal::dateTime($this->confirmed_at),
                'submitted_at' => \Cal::dateTime($this->submitted_at),
                'approved_at' => \Cal::dateTime($this->approved_at),
            ]),
            $this->mergeWhen($this->is_reassessment && $this->has_form && auth()->user()->hasRole('student'), [
                'is_reassessment_applicable' => count($this->reassessment_subjects ?? []) ? true : false,
                'reassessment_subjects' => $this->reassessment_subjects,
            ]),
            'start_date' => \Cal::date($this->start_date),
            'end_date' => \Cal::date($this->end_date),
            'duration' => CalHelper::getDuration($this->start_date, $this->end_date, 'day'),
            'period' => CalHelper::getPeriod($this->start_date, $this->end_date),
            'has_form' => $this->has_form,
            'marksheet_status' => $this->marksheet_status,
            'publish_admit_card' => (bool) $this->getMeta('publish_admit_card'),
            'marksheet_available' => $this->marksheet_available,
            'description' => $this->description,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }

    private function getRecords()
    {
        if (! $this->relationLoaded('records')) {
            return [];
        }

        $examAssessmentRecords = collect($this->assessment->records ?? []);

        $subjectRecords = SubjectRecord::query()
            ->where(function ($q) {
                $q->where('course_id', $this->batch->course_id)
                    ->orWhere('batch_id', $this->batch_id);
            })
            ->whereIn('subject_id', $this->records->pluck('subject_id'))
            ->get();

        return $this->records->map(function ($record) use ($examAssessmentRecords, $subjectRecords) {
            $assessments = collect(Arr::get($record->config, 'assessments', []))
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

            if ($record->subject_id) {
                $isAdditionalSubject = false;
                $subject = SubjectResource::make($record->subject);
                $subjectRecord = $subjectRecords->firstWhere('subject_id', $record->subject_id);
            } else {
                $isAdditionalSubject = true;
                $subject = [
                    'name' => $record->getConfig('subject_name'),
                    'code' => $record->getConfig('subject_code'),
                ];
                $subjectRecord = null;
            }

            $reassessmentRequired = false;
            if ($this->is_reassessment && $this->has_form && auth()->user()->hasRole('student')) {
                $reassessmentSubjects = $this->reassessment_subjects ?? [];
                if (in_array($record->subject->code, $reassessmentSubjects)) {
                    $reassessmentRequired = true;
                }
            }

            $period = null;

            if ($record->start_time->value && $record->duration) {
                $period = $record->start_time->formatted.' - '.$record->end_time->formatted;
            }

            return [
                'uuid' => $record->uuid,
                'subject' => $subject,
                'reassessment_required' => $reassessmentRequired,
                'is_additional_subject' => $isAdditionalSubject,
                'has_exam' => (bool) $record->getConfig('has_exam'),
                'has_grading' => (bool) $subjectRecord?->has_grading,
                'exam_fee' => $subjectRecord?->exam_fee,
                'assessments' => $assessments,
                'sort_date' => $record->date->value,
                'date' => $record->date,
                'start_time' => $record->start_time,
                'duration' => $record->duration,
                'end_time' => $record->end_time,
                'period' => $period,
                'mark_recorded' => (bool) Arr::get($record->config, 'mark_recorded'),
            ];
        });
    }
}
