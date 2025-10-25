<?php

namespace App\Http\Controllers\Mess;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mess\MealRequest;
use App\Http\Resources\Mess\MealResource;
use App\Models\Mess\Meal;
use App\Services\Mess\MealListService;
use App\Services\Mess\MealService;
use Illuminate\Http\Request;

class MealController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, MealService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, MealListService $service)
    {
        return $service->paginate($request);
    }

    public function store(MealRequest $request, MealService $service)
    {
        $meal = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('mess.meal.meal')]),
            'meal' => MealResource::make($meal),
        ]);
    }

    public function show(Meal $meal, MealService $service)
    {
        return MealResource::make($meal);
    }

    public function update(MealRequest $request, Meal $meal, MealService $service)
    {
        $service->update($request, $meal);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('mess.meal.meal')]),
        ]);
    }

    public function destroy(Meal $meal, MealService $service)
    {
        $service->deletable($meal);

        $meal->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('mess.meal.meal')]),
        ]);
    }
}
