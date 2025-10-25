<?php

namespace App\Http\Resources\Employee;

use App\Enums\VerificationStatus;
use App\Http\Resources\MediaResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class AccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $selfUpload = (bool) $this->getMeta('self_upload');

        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'alias' => $this->alias,
            'number' => $this->number,
            'bank_name' => Arr::get($this->bank_details, 'bank_name'),
            'branch_name' => Arr::get($this->bank_details, 'branch_name'),
            'bank_code1' => Arr::get($this->bank_details, 'bank_code1'),
            'bank_code2' => Arr::get($this->bank_details, 'bank_code2'),
            'bank_code3' => Arr::get($this->bank_details, 'bank_code3'),
            'is_verified' => $this->is_verified,
            'self_upload' => $selfUpload,
            'is_primary' => (bool) $this->is_primary,
            $this->mergeWhen($selfUpload, [
                'verification_status' => VerificationStatus::getDetail($this->verification_status),
                'verified_at' => $this->verified_at,
                'comment' => $this->getMeta('comment'),
            ]),
            'media_token' => $this->getMeta('media_token'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }
}
