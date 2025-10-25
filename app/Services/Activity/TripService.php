<?php

namespace App\Services\Activity;

use App\Enums\Employee\AudienceType as EmployeeAudienceType;
use App\Enums\OptionType;
use App\Enums\Student\AudienceType as StudentAudienceType;
use App\Helpers\CalHelper;
use App\Http\Resources\OptionResource;
use App\Models\Activity\Trip;
use App\Models\Option;
use App\Support\HasAudience;
use Illuminate\Http\Request;

class TripService
{
    use HasAudience;

    public function preRequisite(Request $request): array
    {
        $types = OptionResource::collection(Option::query()
            ->byTeam()
            ->whereType(OptionType::TRIP_TYPE->value)
            ->get());

        $studentAudienceTypes = StudentAudienceType::getOptions();

        $employeeAudienceTypes = EmployeeAudienceType::getOptions();

        return compact('types', 'studentAudienceTypes', 'employeeAudienceTypes');
    }

    public function create(Request $request): Trip
    {
        \DB::beginTransaction();

        $trip = Trip::forceCreate($this->formatParams($request));

        $this->storeAudience($trip, $request->all());

        $trip->addMedia($request);

        \DB::commit();

        return $trip;
    }

    private function formatParams(Request $request, ?Trip $trip = null): array
    {
        $startTime = $request->start_time ? CalHelper::storeDateTime($request->start_date.' '.$request->start_time)?->toTimeString() : null;

        $endTime = $request->end_date && $request->end_time ? CalHelper::storeDateTime($request->end_date.' '.$request->end_time)?->toTimeString() : null;

        $formatted = [
            'type_id' => $request->type_id,
            'title' => $request->title,
            'venue' => $request->venue,
            'start_date' => $request->start_date,
            'start_time' => $startTime,
            'end_date' => $request->end_date ?: null,
            'end_time' => $endTime,
            'fees' => [
                [
                    'amount' => $request->fee,
                    'title' => 'trip_fee',
                ],
            ],
            'audience' => [
                'student_type' => $request->student_audience_type,
                'employee_type' => $request->employee_audience_type,
            ],
            'summary' => $request->summary,
            'itinerary' => clean($request->itinerary),
            'description' => clean($request->description),
        ];

        if (! $trip) {
            $formatted['period_id'] = auth()->user()->current_period_id;
        }

        return $formatted;
    }

    public function update(Request $request, Trip $trip): void
    {
        \DB::beginTransaction();

        $this->prepareAudienceForUpdate($trip, $request->all());

        $trip->forceFill($this->formatParams($request, $trip))->save();

        $this->updateAudience($trip, $request->all());

        $trip->updateMedia($request);

        \DB::commit();
    }

    public function deletable(Trip $trip): void
    {
        //
    }
}
