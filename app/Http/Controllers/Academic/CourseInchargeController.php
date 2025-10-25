<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\CourseInchargeRequest;
use App\Http\Resources\Academic\CourseInchargeResource;
use App\Models\Incharge;
use App\Services\Academic\CourseInchargeListService;
use App\Services\Academic\CourseInchargeService;
use Illuminate\Http\Request;

class CourseInchargeController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, CourseInchargeService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, CourseInchargeListService $service)
    {
        $this->authorize('viewAny', Incharge::class, 'course');

        return $service->paginate($request);
    }

    public function store(CourseInchargeRequest $request, CourseInchargeService $service)
    {
        $this->authorize('create', Incharge::class, 'course');

        $courseIncharge = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('academic.course_incharge.course_incharge')]),
            'course_incharge' => CourseInchargeResource::make($courseIncharge),
        ]);
    }

    public function show(string $courseIncharge, CourseInchargeService $service)
    {
        $courseIncharge = Incharge::findByUuidOrFail($courseIncharge);

        $this->authorize('view', $courseIncharge, 'course');

        $courseIncharge->load('model.course');

        return CourseInchargeResource::make($courseIncharge);
    }

    public function update(CourseInchargeRequest $request, string $courseIncharge, CourseInchargeService $service)
    {
        $courseIncharge = Incharge::findByUuidOrFail($courseIncharge);

        $this->authorize('update', $courseIncharge);

        $service->update($request, $courseIncharge, 'course');

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.course_incharge.course_incharge')]),
        ]);
    }

    public function destroy(string $courseIncharge, CourseInchargeService $service)
    {
        $courseIncharge = Incharge::findByUuidOrFail($courseIncharge);

        $this->authorize('delete', $courseIncharge, 'course');

        $service->deletable($courseIncharge);

        $courseIncharge->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('academic.course_incharge.course_incharge')]),
        ]);
    }
}
