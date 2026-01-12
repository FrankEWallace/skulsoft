<?php

namespace App\Http\Resources\Transport;

use App\Enums\Transport\Direction;
use App\Http\Resources\Academic\PeriodResource;
use App\Http\Resources\Transport\Vehicle\VehicleResource;
use App\Domain\Academic\Models\Batch;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class RouteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'max_capacity' => $this->max_capacity,
            'vehicle' => VehicleResource::make($this->whenLoaded('vehicle')),
            'period' => PeriodResource::make($this->whenLoaded('period')),
            'route_stoppages_count' => $this->route_stoppages_count,
            'route_passengers_count' => $this->route_passengers_count,
            'stoppages' => RouteStoppageResource::collection($this->whenLoaded('routeStoppages')),
            $this->mergeWhen($this->whenLoaded('routeStoppages'), [
                'arrival_stoppages' => $this->getArrivalStoppageTimings(),
                'departure_stoppages' => $this->getDepartureStoppageTimings(),
            ]),
            'direction' => Direction::getDetail($this->direction),
            'arrival_starts_at' => $this->arrival_starts_at,
            'departure_starts_at' => $this->departure_starts_at,
            $this->mergeWhen($request->show_passengers === true, [
                'passengers' => $this->getPassengers($request),
            ]),
            $this->mergeWhen($request->show_contact_number === true, [
                'show_contact_number' => true,
            ]),
            'duration_to_destination' => $this->duration_to_destination,
            'description' => $this->description,
            'created_at' => \Cal::dateTime($this->created_at),
            'updated_at' => \Cal::dateTime($this->updated_at),
        ];
    }

    private function getPassengers($request)
    {
        if (! $this->relationLoaded('routePassengers')) {
            return [];
        }

        $batches = Batch::query()
            ->byPeriod()
            ->with('course')
            ->get();

        $records = $this->routePassengers->map(function ($routePassenger) use ($batches, $request) {
            $detail = '';
            $contactNumber = '';
            $subDetail = '';
            $recordPosition = 0;

            if ($routePassenger->model_type == 'Student') {
                $type = ['label' => trans('student.student'), 'value' => 'student'];
                $batch = $batches->firstWhere('id', $routePassenger->model->batch_id);
                $detail = $batch->course->name.' '.$batch->name;
                $subDetail = $routePassenger->model->contact->father_name;
                $recordPosition = ($batch->course->position * 100) + $batch->position;
                if ($request->show_contact_number) {
                    $contactNumber = $routePassenger->model->contact->contact_number;
                }
            } elseif ($routePassenger->model_type == 'Employee') {
                $type = ['label' => trans('employee.employee'), 'value' => 'employee'];
                $detail = $routePassenger->getMeta('title');
                $recordPosition = 0;

                if ($routePassenger->getMeta('publish_contact_number')) {
                    $contactNumber = $routePassenger->model->contact->contact_number;
                }
            }

            return [
                'uuid' => $routePassenger->uuid,
                'type' => $type,
                'record_position' => $recordPosition,
                'stoppage' => $routePassenger->stoppage?->name,
                'name' => $routePassenger->model?->contact->name,
                'detail' => $detail,
                'sub_detail' => $subDetail,
                'contact_number' => $contactNumber,
            ];
        });

        $records = collect($records)->sortBy('record_position')->map(function ($record) {
            $stoppage = $this->routeStoppages->firstWhere('stoppage.name', Arr::get($record, 'stoppage'));
            $record['position'] = Arr::get($stoppage, 'position');

            return $record;
        })->sortBy('position')->values()->toArray();

        return $records;
    }
}
