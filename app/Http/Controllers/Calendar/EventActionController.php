<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use App\Models\Calendar\Event;
use App\Services\Calendar\EventActionService;
use Illuminate\Http\Request;

class EventActionController extends Controller
{
    public function uploadAsset(Request $request, EventActionService $service, string $event, string $type)
    {
        $event = Event::findByUuidOrFail($event);

        $this->authorize('update', $event);

        $service->uploadAsset($request, $event, $type);

        return response()->ok();
    }

    public function pin(Request $request, string $event, EventActionService $service)
    {
        $event = Event::findByUuidOrFail($event);

        $this->authorize('update', $event);

        $service->pin($request, $event);

        return response()->success([
            'message' => trans('global.pinned', ['attribute' => trans('calendar.event.event')]),
        ]);
    }

    public function unpin(Request $request, string $event, EventActionService $service)
    {
        $event = Event::findByUuidOrFail($event);

        $this->authorize('update', $event);

        $service->unpin($request, $event);

        return response()->success([
            'message' => trans('global.unpinned', ['attribute' => trans('calendar.event.event')]),
        ]);
    }
}
