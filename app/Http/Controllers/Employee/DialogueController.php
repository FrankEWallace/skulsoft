<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\DialogueRequest;
use App\Http\Resources\Employee\DialogueResource;
use App\Models\Employee\Employee;
use App\Services\Employee\DialogueListService;
use App\Services\Employee\DialogueService;
use Illuminate\Http\Request;

class DialogueController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, string $employee, DialogueService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('view', $employee);

        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, string $employee, DialogueListService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('view', $employee);

        return $service->paginate($request, $employee);
    }

    public function store(DialogueRequest $request, string $employee, DialogueService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('view', $employee);

        $dialogue = $service->create($request, $employee);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('employee.dialogue.dialogue')]),
            'dialogue' => DialogueResource::make($dialogue),
        ]);
    }

    public function show(string $employee, string $dialogue, DialogueService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('view', $employee);

        $dialogue = $service->findByUuidOrFail($employee, $dialogue);

        $dialogue->load('category', 'media', 'user');

        return DialogueResource::make($dialogue);
    }

    public function update(DialogueRequest $request, string $employee, string $dialogue, DialogueService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('view', $employee);

        $dialogue = $service->findByUuidOrFail($employee, $dialogue);

        $service->update($request, $employee, $dialogue);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('employee.dialogue.dialogue')]),
        ]);
    }

    public function destroy(string $employee, string $dialogue, DialogueService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('view', $employee);

        $dialogue = $service->findByUuidOrFail($employee, $dialogue);

        $service->deletable($employee, $dialogue);

        $dialogue->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('employee.dialogue.dialogue')]),
        ]);
    }

    public function downloadMedia(string $employee, string $dialogue, string $uuid, DialogueService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('view', $employee);

        $dialogue = $service->findByUuidOrFail($employee, $dialogue);

        return $dialogue->downloadMedia($uuid);
    }
}
