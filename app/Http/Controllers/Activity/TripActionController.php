<?php

namespace App\Http\Controllers\Activity;

use App\Http\Controllers\Controller;
use App\Models\Activity\Trip;
use App\Services\Activity\TripActionService;
use Illuminate\Http\Request;

class TripActionController extends Controller
{
    public function uploadAsset(Request $request, TripActionService $service, string $trip, string $type)
    {
        $trip = Trip::findByUuidOrFail($trip);

        $this->authorize('update', $trip);

        $service->uploadAsset($request, $trip, $type);

        return response()->ok();
    }

    public function uploadMedia(Request $request, string $trip, TripActionService $service)
    {
        $trip = Trip::findByUuidOrFail($trip);

        $this->authorize('update', $trip);

        $service->uploadMedia($request, $trip);

        return response()->success([
            'message' => trans('global.uploaded', ['attribute' => trans('general.file')]),
        ]);
    }

    public function removeMedia(string $trip, string $uuid, TripActionService $service)
    {
        $trip = Trip::findByUuidOrFail($trip);

        $this->authorize('update', $trip);

        $service->removeMedia($trip, $uuid);

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('general.file')]),
        ]);
    }
}
