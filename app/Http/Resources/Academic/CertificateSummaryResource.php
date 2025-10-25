<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class CertificateSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $to = [];
        if ($this->model_type == 'Student') {
            $to = [
                'name' => $this->to_name,
                'code_number' => $this->admission_number,
            ];
        } elseif ($this->model_type == 'Employee') {
            $to = [
                'name' => $this->to_name,
                'code_number' => $this->employee_number,
            ];
        } else {
            $to = [
                'name' => $this->getMeta('name'),
            ];
        }

        return [
            'uuid' => $this->uuid,
            'code_number' => $this->code_number,
            'date' => $this->date,
            'is_duplicate' => $this->is_duplicate,
            'template' => CertificateTemplateSummaryResource::make($this->whenLoaded('template')),
            'to' => $to,
            'custom_fields' => $this->getCustomFields(),
            'is_anonymous' => empty($this->model_type) ? true : false,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }

    private function getCustomFields()
    {
        if (! $this->relationLoaded('template')) {
            return [];
        }

        $template = $this->template;

        $customFields = [];
        foreach ($template->custom_fields as $field) {
            if (! Arr::get($field, 'show_label')) {
                continue;
            }

            $value = Arr::get($this->custom_fields, $field['name']);

            $customFields[] = [
                'label' => $field['name'],
                'value' => $value,
            ];
        }

        return $customFields;
    }
}
