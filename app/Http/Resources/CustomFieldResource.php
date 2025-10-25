<?php

namespace App\Http\Resources;

use App\Enums\CustomFieldForm;
use App\Enums\CustomFieldType;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class CustomFieldResource extends JsonResource
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
            'form' => CustomFieldForm::getDetail($this->form),
            'type' => CustomFieldType::getDetail($this->type),
            'name' => Str::camel($this->label),
            'label' => $this->label,
            'name' => Str::camel($this->label),
            'is_required' => $this->is_required,
            'position' => $this->position,
            $this->mergeWhen(in_array($this->type, [CustomFieldType::NUMBER_INPUT, CustomFieldType::CURRENCY_INPUT]), [
                'min_value' => $this->getConfig('min_value'),
                'max_value' => $this->getConfig('max_value'),
            ]),
            $this->mergeWhen(in_array($this->type, [CustomFieldType::TEXT_INPUT, CustomFieldType::MULTI_LINE_TEXT_INPUT]), [
                'min_length' => $this->getConfig('min_length'),
                'max_length' => $this->getConfig('max_length'),
            ]),
            $this->mergeWhen(in_array($this->type, [CustomFieldType::SELECT_INPUT, CustomFieldType::MULTI_SELECT_INPUT, CustomFieldType::RADIO_INPUT, CustomFieldType::CHECKBOX_INPUT]), [
                'options' => $this->getConfig('options'),
                'option_array' => collect(explode(',', $this->getConfig('options')))->map(function ($item) {
                    return [
                        'label' => trim($item),
                        'value' => trim($item),
                    ];
                })->toArray(),
            ]),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
