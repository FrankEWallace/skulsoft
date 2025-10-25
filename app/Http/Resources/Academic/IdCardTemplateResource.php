<?php

namespace App\Http\Resources\Academic;

use App\Enums\Academic\IdCardFor;
use Illuminate\Http\Resources\Json\JsonResource;

class IdCardTemplateResource extends JsonResource
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
            'for' => IdCardFor::getDetail($this->for),
            'has_custom_template_file' => (bool) $this->getConfig('custom_template_file_name'),
            'custom_template_file_name' => $this->getConfig('custom_template_file_name'),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
