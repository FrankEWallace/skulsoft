<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student\Student;
use App\Services\Student\StudentActionService;
use Illuminate\Http\Request;

class StudentActionController extends Controller
{
    public function __construct()
    {
        //
    }

    public function setDefaultPeriod(Request $request, string $student, StudentActionService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('update', $student);

        $service->setDefaultPeriod($request, $student);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.student')]),
        ]);
    }

    public function updateTags(Request $request, string $student, StudentActionService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('update', $student);

        $service->updateTags($request, $student);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.student')]),
        ]);
    }

    public function updateBulkTags(Request $request, StudentActionService $service)
    {
        $this->authorize('bulkUpdate', Student::class);

        $service->updateBulkTags($request);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.student')]),
        ]);
    }

    public function updateBulkGroups(Request $request, StudentActionService $service)
    {
        $this->authorize('bulkUpdate', Student::class);

        $service->updateBulkGroups($request);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.student')]),
        ]);
    }

    public function updateBulkMentor(Request $request, StudentActionService $service)
    {
        $this->authorize('bulkUpdate', Student::class);

        $service->updateBulkMentor($request);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.student')]),
        ]);
    }

    public function updateBulkEnrollmentType(Request $request, StudentActionService $service)
    {
        $this->authorize('bulkUpdate', Student::class);

        $service->updateBulkEnrollmentType($request);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.student')]),
        ]);
    }

    public function updateBulkEnrollmentStatus(Request $request, StudentActionService $service)
    {
        $this->authorize('bulkUpdate', Student::class);

        $service->updateBulkEnrollmentStatus($request);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.student')]),
        ]);
    }
}
