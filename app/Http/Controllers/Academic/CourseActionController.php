<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\CourseBatchRequest;
use App\Domain\Academic\Models\Batch;
use App\Domain\Academic\Models\Course;
use App\Services\Academic\CourseActionService;
use Illuminate\Http\Request;

class CourseActionController extends Controller
{
    public function updateConfig(Request $request, string $course, CourseActionService $service)
    {
        $course = Course::findByUuidOrFail($course);

        $this->authorize('update', $course);

        $service->updateConfig($request, $course);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.course.course')]),
        ]);
    }

    public function addBatches(CourseBatchRequest $request, string $course, CourseActionService $service)
    {
        $course = Course::findByUuidOrFail($course);

        $this->authorize('create', Batch::class);

        $service->addBatches($request, $course);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('academic.batch.batch')]),
        ]);
    }

    public function reorder(Request $request, CourseActionService $service)
    {
        $this->authorize('create', Course::class);

        $menu = $service->reorder($request);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.course.course')]),
        ]);
    }

    public function reorderBatch(Request $request, CourseActionService $service)
    {
        $this->authorize('create', Course::class);

        $menu = $service->reorderBatch($request);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.course.course')]),
        ]);
    }

    public function updateCurrentPeriod(Request $request, string $course, CourseActionService $service)
    {
        $course = Course::findByUuidOrFail($course);

        $this->authorize('update', $course);

        $service->updateCurrentPeriod($request, $course);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.course.course')]),
        ]);
    }

    public function updateEnrollmentSeat(Request $request, string $course, CourseActionService $service)
    {
        $course = Course::findByUuidOrFail($course);

        $this->authorize('update', $course);

        $service->updateEnrollmentSeat($request, $course);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.course.course')]),
        ]);
    }
}
