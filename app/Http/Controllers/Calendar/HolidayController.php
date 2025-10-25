<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use App\Http\Requests\Calendar\HolidayRequest;
use App\Http\Resources\Calendar\HolidayResource;
use App\Models\Calendar\Holiday;
use App\Services\Calendar\HolidayListService;
use App\Services\Calendar\HolidayService;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, HolidayService $service)
    {
        $this->authorize('preRequisite', Holiday::class);

        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, HolidayListService $service)
    {
        $this->authorize('viewAny', Holiday::class);

        return $service->paginate($request);
    }

    public function store(HolidayRequest $request, HolidayService $service)
    {
        $this->authorize('create', Holiday::class);

        $holiday = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('calendar.holiday.holiday')]),
            'holiday' => HolidayResource::make($holiday),
        ]);
    }

    public function show(Holiday $holiday, HolidayService $service)
    {
        $this->authorize('view', $holiday);

        return HolidayResource::make($holiday);
    }

    public function update(HolidayRequest $request, Holiday $holiday, HolidayService $service)
    {
        $this->authorize('update', $holiday);

        $service->update($request, $holiday);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('calendar.holiday.holiday')]),
        ]);
    }

    public function destroy(Holiday $holiday, HolidayService $service)
    {
        $this->authorize('delete', $holiday);

        $service->deletable($holiday);

        $holiday->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('calendar.holiday.holiday')]),
        ]);
    }
}
