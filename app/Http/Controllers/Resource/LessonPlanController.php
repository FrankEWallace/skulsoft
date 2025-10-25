<?php

namespace App\Http\Controllers\Resource;

use App\Http\Controllers\Controller;
use App\Http\Requests\Resource\LessonPlanRequest;
use App\Http\Resources\Resource\LessonPlanResource;
use App\Models\Resource\LessonPlan;
use App\Services\Resource\LessonPlanListService;
use App\Services\Resource\LessonPlanService;
use Illuminate\Http\Request;

class LessonPlanController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, LessonPlanService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, LessonPlanListService $service)
    {
        $this->authorize('viewAny', LessonPlan::class);

        return $service->paginate($request);
    }

    public function store(LessonPlanRequest $request, LessonPlanService $service)
    {
        $this->authorize('create', LessonPlan::class);

        $lessonPlan = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('resource.lesson_plan.lesson_plan')]),
            'lesson_plan' => LessonPlanResource::make($lessonPlan),
        ]);
    }

    public function show(Request $request, string $lessonPlan, LessonPlanService $service)
    {
        $lessonPlan = LessonPlan::findByUuidOrFail($lessonPlan);

        $this->authorize('view', $lessonPlan);

        $lessonPlan->load(['records.subject', 'records.batch.course', 'employee' => fn ($q) => $q->summary(), 'media']);

        return LessonPlanResource::make($lessonPlan);
    }

    public function update(LessonPlanRequest $request, string $lessonPlan, LessonPlanService $service)
    {
        $lessonPlan = LessonPlan::findByUuidOrFail($lessonPlan);

        $this->authorize('update', $lessonPlan);

        $service->update($request, $lessonPlan);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('resource.lesson_plan.lesson_plan')]),
        ]);
    }

    public function destroy(string $lessonPlan, LessonPlanService $service)
    {
        $lessonPlan = LessonPlan::findByUuidOrFail($lessonPlan);

        $this->authorize('delete', $lessonPlan);

        $service->deletable($lessonPlan);

        $lessonPlan->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('resource.lesson_plan.lesson_plan')]),
        ]);
    }

    public function downloadMedia(string $lessonPlan, string $uuid, LessonPlanService $service)
    {
        $lessonPlan = LessonPlan::findByUuidOrFail($lessonPlan);

        $this->authorize('view', $lessonPlan);

        return $lessonPlan->downloadMedia($uuid);
    }
}
