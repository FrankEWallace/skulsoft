<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\CourseRequest;
use App\Http\Resources\Academic\CourseResource;
use App\Domain\Academic\Models\Course;
use App\Services\Academic\CourseListService;
use App\Services\Academic\CourseService;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, CourseService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, CourseListService $service)
    {
        $this->authorize('viewAny', Course::class);

        return $service->paginate($request);
    }

    public function store(CourseRequest $request, CourseService $service)
    {
        $this->authorize('create', Course::class);

        $course = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('academic.course.course')]),
            'course' => CourseResource::make($course),
        ]);
    }

    public function show(Request $request, string $course, CourseService $service)
    {
        $course = Course::findByUuidOrFail($course);

        $this->authorize('view', $course);

        $course->load('division', 'batches', 'batches.subjectRecords.subject', 'enrollmentSeats', 'subjectRecords.subject');

        $request->merge([
            'details' => true,
        ]);

        return CourseResource::make($course);
    }

    public function update(CourseRequest $request, string $course, CourseService $service)
    {
        $course = Course::findByUuidOrFail($course);

        $this->authorize('update', $course);

        $service->update($request, $course);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.course.course')]),
        ]);
    }

    public function destroy(string $course, CourseService $service)
    {
        $course = Course::findByUuidOrFail($course);

        $this->authorize('delete', $course);

        $service->deletable($course);

        $course->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('academic.course.course')]),
        ]);
    }
}
