<?php

namespace App\Http\Resources\Academic;

use App\Enums\Academic\CertificateFor;
use App\Enums\Academic\CertificateType;
use Illuminate\Http\Resources\Json\JsonResource;

class CertificateTemplateResource extends JsonResource
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
            'number_prefix' => $this->getConfig('number_prefix'),
            'number_digit' => $this->getConfig('number_digit'),
            'number_suffix' => $this->getConfig('number_suffix'),
            'type' => CertificateType::getDetail($this->type),
            'for' => CertificateFor::getDetail($this->for),
            'variables' => $this->for->variable(),
            'custom_fields' => $this->detailed_custom_fields,
            'has_custom_template_file' => (bool) $this->getConfig('has_custom_template_file'),
            'custom_template_file_name' => $this->getConfig('custom_template_file_name'),
            'has_custom_header' => (bool) $this->getConfig('has_custom_header'),
            'content' => $this->content,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
