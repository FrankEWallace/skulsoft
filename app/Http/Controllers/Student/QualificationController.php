<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\QualificationRequest;
use App\Http\Resources\Student\QualificationResource;
use App\Models\Student\Student;
use App\Services\Student\QualificationListService;
use App\Services\Student\QualificationService;
use Illuminate\Http\Request;

class QualificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, string $student, QualificationService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, string $student, QualificationListService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        return $service->paginate($request, $student);
    }

    public function store(QualificationRequest $request, string $student, QualificationService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('manageRecord', $student);

        $qualification = $service->create($request, $student);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('student.qualification.qualification')]),
            'qualification' => QualificationResource::make($qualification),
        ]);
    }

    public function show(string $student, string $qualification, QualificationService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        $qualification = $service->findByUuidOrFail($student, $qualification);

        $qualification->load('level', 'media');

        return QualificationResource::make($qualification);
    }

    public function update(QualificationRequest $request, string $student, string $qualification, QualificationService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('manageRecord', $student);

        $qualification = $service->findByUuidOrFail($student, $qualification);

        $service->update($request, $student, $qualification);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.qualification.qualification')]),
        ]);
    }

    public function destroy(string $student, string $qualification, QualificationService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('manageRecord', $student);

        $qualification = $service->findByUuidOrFail($student, $qualification);

        $service->deletable($student, $qualification);

        $qualification->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('student.qualification.qualification')]),
        ]);
    }

    public function downloadMedia(string $student, string $qualification, string $uuid, QualificationService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        $qualification = $service->findByUuidOrFail($student, $qualification);

        return $qualification->downloadMedia($uuid);
    }
}
