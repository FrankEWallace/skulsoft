<?php

namespace App\Http\Resources\Finance;

use App\Enums\Finance\LedgerGroup;
use Illuminate\Http\Resources\Json\JsonResource;

class LedgerTypeResource extends JsonResource
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
            'type' => LedgerGroup::getDetail($this->type),
            'alias' => $this->alias,
            'description' => $this->description,
            'is_default' => $this->is_default,
            'has_account' => $this->has_account,
            'has_contact' => $this->has_contact,
            'has_code_number' => $this->has_code_number,
            'parent' => self::make($this->whenLoaded('parent')),
            'children' => self::collection($this->whenLoaded('children')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
