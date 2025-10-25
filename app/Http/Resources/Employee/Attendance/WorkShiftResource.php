<?php

namespace App\Http\Resources\Employee\Attendance;

use App\Enums\Day;
use App\Helpers\CalHelper;
use App\Http\Resources\TeamResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class WorkShiftResource extends JsonResource
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
            'code' => $this->code,
            'records' => $this->getRecords(),
            'description' => $this->description,
            'team' => TeamResource::make($this->whenLoaded('team')),
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }

    private function getRecords(): array
    {
        $records = [];

        foreach ($this->records as $record) {
            $startTime = Arr::get($record, 'start_time');
            $endTime = Arr::get($record, 'end_time');

            $records[] = [
                ...$record,
                'start_time' => CalHelper::toTime($startTime),
                'end_time' => CalHelper::toTime($endTime),
                'label' => Day::getLabel(Arr::get($record, 'day')),
                'start_time_display' => CalHelper::showTime($startTime),
                'end_time_display' => CalHelper::showTime($endTime),
            ];
        }

        return $records;
    }
}
