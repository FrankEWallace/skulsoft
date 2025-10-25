<?php

namespace App\Http\Resources\Form;

use App\Enums\CustomFieldType;
use App\Http\Resources\MediaResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class FormSubmissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $detail = null;
        if ($this->model_type == 'Student') {
            $codeNumber = $this->admission_number;
            $detail = $this->course_name.' '.$this->course_term.' '.$this->batch_name;
            $type = trans('student.student');
        } else {
            $codeNumber = $this->employee_code;
            $type = trans('employee.employee');
        }

        return [
            'uuid' => $this->uuid,
            'name' => $this->model?->contact->name,
            'code_number' => $codeNumber,
            'detail' => $detail,
            'type' => [
                'value' => strtolower($this->model_type),
                'label' => $type,
            ],
            $this->mergeWhen($this->relationLoaded('records'), [
                'responses' => $this->getResponses($request),
            ]),
            'submitted_at' => $this->submitted_at,
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => \Cal::dateTime($this->created_at),
        ];
    }

    private function getResponses(Request $request): array
    {
        $responses = [];

        foreach ($this->records as $record) {
            $response = $record->response;

            if (in_array($record->field->type, [CustomFieldType::DATE_PICKER])) {
                $response = \Cal::date($response)?->formatted;
            } elseif (in_array($record->field->type, [CustomFieldType::TIME_PICKER])) {
                $response = \Cal::time($response)?->formatted;
            } elseif (in_array($record->field->type, [CustomFieldType::DATE_TIME_PICKER])) {
                $response = \Cal::dateTime($response)?->formatted;
            }

            if (in_array($record->field->type, [CustomFieldType::CAMERA_IMAGE])) {
                $response = collect($record->getMeta('images', []))->map(function ($image) {
                    return url('/storage/'.$image);
                });
            }

            if ($request->query('export')) {
                $responses[$record->field->uuid] = [
                    'uuid' => $record->field->uuid,
                    'type' => $record->field->type,
                    'slug' => Str::slug($record->field->name),
                    'value' => $response,
                ];
            } else {
                $responses[] = [
                    'uuid' => $record->field->uuid,
                    'type' => $record->field->type,
                    'slug' => Str::slug($record->field->name),
                    'value' => $response,
                ];
            }

        }

        return $responses;
    }
}
