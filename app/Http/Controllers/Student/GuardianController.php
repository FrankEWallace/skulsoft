<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\GuardianRequest;
use App\Http\Resources\GuardianResource;
use App\Models\Guardian;
use App\Models\Student\Student;
use App\Services\Student\GuardianListService;
use App\Services\Student\GuardianService;
use Illuminate\Http\Request;

class GuardianController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, string $student, GuardianService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('update', $student);

        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, string $student, GuardianListService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        return $service->paginate($request, $student);
    }

    public function store(GuardianRequest $request, string $student, GuardianService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('update', $student);

        $this->authorize('create', Guardian::class);

        $guardian = $service->create($request, $student);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('guardian.guardian')]),
            'guardian' => GuardianResource::make($guardian),
        ]);
    }

    public function show(string $student, string $guardian, GuardianService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        $guardian = Guardian::query()
            ->with('contact')
            ->filterByPrimaryContact($student->contact_id)
            ->findByUuidOrFail($guardian);

        return GuardianResource::make($guardian);
    }

    public function update(GuardianRequest $request, string $student, string $guardian, GuardianService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('update', $student);

        $guardian = Guardian::query()
            ->with('contact')
            ->filterByPrimaryContact($student->contact_id)
            ->findByUuidOrFail($guardian);

        $this->authorize('update', $guardian);

        $service->update($request, $student, $guardian);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('guardian.guardian')]),
        ]);
    }

    public function destroy(string $student, string $guardian, GuardianService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('update', $student);

        $guardian = Guardian::query()
            ->with('contact')
            ->filterByPrimaryContact($student->contact_id)
            ->findByUuidOrFail($guardian);

        $this->authorize('delete', $guardian);

        $service->deletable($student, $guardian);

        $guardian->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('guardian.guardian')]),
        ]);
    }
}
