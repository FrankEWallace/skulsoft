<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use App\Http\Requests\Calendar\EventRequest;
use App\Http\Resources\Calendar\EventResource;
use App\Models\Calendar\Event;
use App\Services\Calendar\EventListService;
use App\Services\Calendar\EventService;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, EventService $service)
    {
        $this->authorize('preRequisite', Event::class);

        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, EventListService $service)
    {
        $this->authorize('viewAny', Event::class);

        return $service->paginate($request);
    }

    public function store(EventRequest $request, EventService $service)
    {
        $this->authorize('create', Event::class);

        $event = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('calendar.event.event')]),
            'event' => EventResource::make($event),
        ]);
    }

    public function show(Request $request, string $event, EventService $service)
    {
        $event = Event::findByUuidOrFail($event);

        $this->authorize('view', $event);

        $event->load([
            'audiences.audienceable',
            'type',
            'media',
            'incharges.employee' => fn ($q) => $q->detail(),
        ]);

        $request->merge([
            'show_details' => true,
        ]);

        return EventResource::make($event);
    }

    public function update(EventRequest $request, string $event, EventService $service)
    {
        $event = Event::findByUuidOrFail($event);

        $this->authorize('update', $event);

        $service->update($request, $event);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('calendar.event.event')]),
        ]);
    }

    public function destroy(string $event, EventService $service)
    {
        $event = Event::findByUuidOrFail($event);

        $this->authorize('delete', $event);

        $service->deletable($event);

        $event->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('calendar.event.event')]),
        ]);
    }

    public function downloadMedia(string $event, string $uuid, EventService $service)
    {
        $event = Event::findByUuidOrFail($event);

        $this->authorize('view', $event);

        return $event->downloadMedia($uuid);
    }
}
