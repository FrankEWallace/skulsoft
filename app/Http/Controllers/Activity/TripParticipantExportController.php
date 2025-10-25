<?php

namespace App\Http\Controllers\Activity;

use App\Http\Controllers\Controller;
use App\Models\Activity\Trip;
use App\Services\Activity\TripParticipantListService;
use Illuminate\Http\Request;

class TripParticipantExportController extends Controller
{
    public function __invoke(Request $request, string $trip, TripParticipantListService $service)
    {
        $trip = Trip::findByUuidOrFail($trip);

        $list = $service->list($request, $trip);

        return $service->export($list);
    }
}
