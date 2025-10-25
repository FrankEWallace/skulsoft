<?php

namespace App\Http\Controllers\Activity;

use App\Http\Controllers\Controller;
use App\Http\Requests\Activity\TripRequest;
use App\Http\Resources\Activity\TripResource;
use App\Models\Activity\Trip;
use App\Services\Activity\TripListService;
use App\Services\Activity\TripService;
use Illuminate\Http\Request;

class TripController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, TripService $service)
    {
        $this->authorize('preRequisite', Trip::class);

        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, TripListService $service)
    {
        $this->authorize('viewAny', Trip::class);

        return $service->paginate($request);
    }

    public function store(TripRequest $request, TripService $service)
    {
        $this->authorize('create', Trip::class);

        $trip = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('activity.trip.trip')]),
            'trip' => TripResource::make($trip),
        ]);
    }

    public function show(Request $request, string $trip, TripService $service)
    {
        $trip = Trip::query()
            ->withCount('participants')
            ->findByUuidOrFail($trip);

        $this->authorize('view', $trip);

        $request->merge(['show_detail' => true]);

        $trip->load([
            'audiences.audienceable',
            'type',
            'media',
        ]);

        return TripResource::make($trip);
    }

    public function update(TripRequest $request, string $trip, TripService $service)
    {
        $trip = Trip::findByUuidOrFail($trip);

        $this->authorize('update', $trip);

        $service->update($request, $trip);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('activity.trip.trip')]),
        ]);
    }

    public function destroy(string $trip, TripService $service)
    {
        $trip = Trip::findByUuidOrFail($trip);

        $this->authorize('delete', $trip);

        $service->deletable($trip);

        $trip->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('activity.trip.trip')]),
        ]);
    }

    public function downloadMedia(string $trip, string $uuid, TripService $service)
    {
        $trip = Trip::findByUuidOrFail($trip);

        $this->authorize('view', $trip);

        return $trip->downloadMedia($uuid);
    }
}
