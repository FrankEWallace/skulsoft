<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mess\MealLogRequest;
use App\Http\Resources\Mess\MealLogResource;
use App\Models\Mess\MealLog;
use App\Services\Mess\MealLogListService;
use App\Services\Mess\MealLogService;
use Illuminate\Http\Request;

class MealLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, MealLogService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, MealLogListService $service)
    {
        $this->authorize('viewAny', MealLog::class);

        return $service->paginate($request);
    }

    public function store(MealLogRequest $request, MealLogService $service)
    {
        $this->authorize('create', MealLog::class);

        $mealLog = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('mess.meal.log.log')]),
            'meal_log' => MealLogResource::make($mealLog),
        ]);
    }

    public function show(MealLog $mealLog, MealLogService $service)
    {
        $this->authorize('view', $mealLog);

        $mealLog->load('meal', 'records.item');

        return MealLogResource::make($mealLog);
    }

    public function update(MealLogRequest $request, MealLog $mealLog, MealLogService $service)
    {
        $this->authorize('update', $mealLog);

        $service->update($request, $mealLog);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('mess.meal.log.log')]),
        ]);
    }

    public function destroy(MealLog $mealLog, MealLogService $service)
    {
        $this->authorize('delete', $mealLog);

        $service->deletable($mealLog);

        $mealLog->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('mess.meal.log.log')]),
        ]);
    }
}
