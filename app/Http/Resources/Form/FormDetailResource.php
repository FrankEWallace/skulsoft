<?php

namespace App\Http\Resources\Form;

use App\Enums\CustomFieldType;
use App\Http\Resources\MediaResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class FormDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $fields = [];

        $submission = $this->submissions?->first();
        $submissionRecords = $submission?->records ?? collect([]);

        $submissionMedia = $submission?->media ?? collect([]);

        foreach ($this->fields as $key => $formField) {
            $field['uuid'] = $formField->uuid;
            $field['type'] = $formField->type->value;
            $field['label'] = $formField->label;
            $field['content'] = $formField->content;
            $field['placeholder'] = strtoupper(Str::snake($formField->label));
            $field['name'] = $formField->name;
            $field['slug'] = Str::slug($formField->name);
            $field['is_required'] = (bool) $formField->is_required;

            $submissionRecord = $submissionRecords?->firstWhere('field_id', $formField->id);

            $field['value'] = $submissionRecord?->response;

            $field['show_type'] = true;
            if (in_array($formField->type, [CustomFieldType::PARAGRAPH])) {
                $field['show_type'] = false;
            }

            $field['is_date_type'] = false;

            if ($formField->type == CustomFieldType::DATE_PICKER) {
                $field['value'] = \Cal::date($field['value']);
                $field['is_date_type'] = true;
            } elseif ($formField->type == CustomFieldType::TIME_PICKER) {
                $field['value'] = \Cal::time($field['value']);
                $field['is_date_type'] = true;
            } elseif ($formField->type == CustomFieldType::DATE_TIME_PICKER) {
                $field['value'] = \Cal::dateTime($field['value']);
                $field['is_date_type'] = true;
            }

            if (in_array($formField->type, [CustomFieldType::SELECT_INPUT, CustomFieldType::MULTI_SELECT_INPUT, CustomFieldType::CHECKBOX_INPUT, CustomFieldType::RADIO_INPUT])) {
                $field['option_array'] = array_map('trim', explode(',', $formField->getConfig('options') ?? []));
                $field['option_array'] = array_map(fn ($option) => ['label' => $option, 'value' => $option], $field['option_array']);
            }

            if (in_array($formField->type, [CustomFieldType::CAMERA_IMAGE])) {
                $field['value'] = collect($submissionRecord?->getMeta('images') ?? [])->map(function ($image) {
                    return [
                        'id' => uniqid(),
                        'path' => $image,
                        'url' => url('/storage/'.$image),
                    ];
                });
            }

            // $field['value'] = in_array($formField->type, [CustomFieldType::CHECKBOX_INPUT, CustomFieldType::MULTI_SELECT_INPUT]) ? [] : '';

            $fields[] = $field;
        }

        return [
            'uuid' => $this->uuid,
            'submission_uuid' => $submission?->uuid,
            'name' => $this->name,
            'can_submit' => (bool) $this->can_submit,
            'published_at' => $this->published_at,
            'due_date' => $this->due_date,
            'submitted_at' => \Cal::dateTime($this->submitted_at),
            'is_submitted' => $this->submitted_at ? true : false,
            'is_expired' => empty($this->submitted_at) && $this->due_date->value < today()->toDateString() ? true : false,
            'fields' => $fields,
            'media_token' => $this->getMeta('media_token'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'submission_media' => MediaResource::collection($submissionMedia),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
