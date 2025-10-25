<?php

namespace App\Http\Resources\Form;

use App\Enums\CustomFieldType;
use Illuminate\Http\Resources\Json\JsonResource;

class FieldResource extends JsonResource
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
            'type' => CustomFieldType::getDetail($this->type),
            'label' => $this->label,
            'name' => $this->name,
            'show_label' => $this->type == CustomFieldType::PARAGRAPH ? false : true,
            'show_value' => $this->type != CustomFieldType::PARAGRAPH ? true : false,
            'content' => $this->content,
            'is_required' => (bool) $this->is_required,
            'min_length' => $this->getConfig('min_length'),
            'max_length' => $this->getConfig('max_length'),
            'min_value' => $this->getConfig('min_value'),
            'max_value' => $this->getConfig('max_value'),
            'options' => $this->getConfig('options'),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
