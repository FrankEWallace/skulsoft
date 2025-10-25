<?php

namespace App\Http\Resources\Student;

use App\Enums\ServiceRequestStatus;
use App\Enums\ServiceRequestType;
use App\Enums\ServiceType;
use App\Http\Resources\MediaResource;
use App\Http\Resources\Transport\StoppageResource;
use App\Http\Resources\UserSummaryResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class ServiceRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $requestRecords = $this->getRequestRecords();

        return [
            'uuid' => $this->uuid,
            'code_number' => $this->code_number,
            'student' => StudentSummaryResource::make($this->whenLoaded('model')),
            'requester' => UserSummaryResource::make($this->whenLoaded('requester')),
            'type' => ServiceType::getDetail($this->type),
            'request_type' => ServiceRequestType::getDetail($this->request_type),
            $this->mergeWhen($requestRecords, [
                'request_records' => $requestRecords,
                'comment' => Arr::get(Arr::first($requestRecords), 'comment'),
                'remarks' => Arr::get(Arr::first($requestRecords), 'remarks'),
            ]),
            'date' => $this->date,
            'description' => $this->description,
            'status' => ServiceRequestStatus::getDetail($this->status),
            'is_editable' => $this->is_editable,
            'transport_stoppage' => StoppageResource::make($this->whenLoaded('transportStoppage')),
            'media_token' => $this->getMeta('media_token'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }

    private function getRequestRecords()
    {
        if (! $this->relationLoaded('requestRecords')) {
            return [];
        }

        $requestRecords = $this->requestRecords->sortByDesc('created_at');

        return $requestRecords->map(fn ($record) => [
            'user' => UserSummaryResource::make($record->user),
            'comment' => $record->comment,
            'remarks' => $record->remarks,
            'created_at' => \Cal::dateTime($record->created_at),
        ]);
    }
}
