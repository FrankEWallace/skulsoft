<?php

namespace App\Services\Activity;

use App\Models\Activity\Trip;
use App\Models\Activity\TripParticipant;
use Illuminate\Http\Request;

class TripParticipantService
{
    public function findByUuidOrFail(Trip $trip, string $uuid): TripParticipant
    {
        return TripParticipant::query()
            ->whereTripId($trip->id)
            ->whereUuid($uuid)
            ->getOrFail(trans('trip.participant.participant'));
    }

    private function validateParticipant(Request $request): void
    {
        // validate participant as audience
    }

    public function create(Request $request, Trip $trip): TripParticipant
    {
        $this->validateParticipant($request);

        \DB::beginTransaction();

        $participant = TripParticipant::forceCreate($this->formatParams($request, $trip));

        \DB::commit();

        return $participant;
    }

    private function formatParams(Request $request, Trip $trip, ?TripParticipant $participant = null): array
    {
        $formatted = [
            'amount' => $request->fee,
            'paid' => $request->paid,
            'model_type' => $request->type == 'student' ? 'Student' : 'Employee',
            'model_id' => $request->participant_id,
        ];

        if (! $participant) {
            $formatted['trip_id'] = $trip->id;
        }

        $meta = $participant?->meta ?? [];
        $meta['payments'] = $request->payments;

        $formatted['meta'] = $meta;

        return $formatted;
    }

    public function update(Request $request, Trip $trip, TripParticipant $participant): void
    {
        $this->validateParticipant($request);

        \DB::beginTransaction();

        $participant->forceFill($this->formatParams($request, $trip, $participant))->save();

        \DB::commit();
    }

    public function deletable(Trip $trip, TripParticipant $participant): void
    {
        //
    }
}
