<?php

namespace App\Http\Controllers\Activity;

use App\Http\Controllers\Controller;
use App\Http\Requests\Activity\TripParticipantRequest;
use App\Http\Resources\Activity\TripParticipantResource;
use App\Models\Activity\Trip;
use App\Services\Activity\TripParticipantListService;
use App\Services\Activity\TripParticipantService;
use Illuminate\Http\Request;

class TripParticipantController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function index(Request $request, string $trip, TripParticipantListService $service)
    {
        $trip = Trip::findByUuidOrFail($trip);

        $this->authorize('update', $trip);

        return $service->paginate($request, $trip);
    }

    public function store(TripParticipantRequest $request, string $trip, TripParticipantService $service)
    {
        $trip = Trip::findByUuidOrFail($trip);

        $this->authorize('update', $trip);

        $participant = $service->create($request, $trip);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('activity.trip.participant.participant')]),
            'participant' => TripParticipantResource::make($participant),
        ]);
    }

    public function show(Request $request, string $trip, string $participant, TripParticipantService $service)
    {
        $trip = Trip::findByUuidOrFail($trip);

        $this->authorize('update', $trip);

        $participant = $service->findByUuidOrFail($trip, $participant);

        return TripParticipantResource::make($participant);
    }

    public function update(TripParticipantRequest $request, string $trip, string $participant, TripParticipantService $service)
    {
        $trip = Trip::findByUuidOrFail($trip);

        $this->authorize('update', $trip);

        $participant = $service->findByUuidOrFail($trip, $participant);

        $service->update($request, $trip, $participant);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('activity.trip.participant.participant')]),
        ]);
    }

    public function destroy(string $trip, string $participant, TripParticipantService $service)
    {
        $trip = Trip::findByUuidOrFail($trip);

        $this->authorize('update', $trip);

        $participant = $service->findByUuidOrFail($trip, $participant);

        $service->deletable($trip, $participant);

        $participant->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('activity.trip.participant.participant')]),
        ]);
    }
}
