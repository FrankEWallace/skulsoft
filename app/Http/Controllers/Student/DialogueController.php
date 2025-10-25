<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\DialogueRequest;
use App\Http\Resources\Student\DialogueResource;
use App\Models\Student\Student;
use App\Services\Student\DialogueListService;
use App\Services\Student\DialogueService;
use Illuminate\Http\Request;

class DialogueController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, string $student, DialogueService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, string $student, DialogueListService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        return $service->paginate($request, $student);
    }

    public function store(DialogueRequest $request, string $student, DialogueService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        $dialogue = $service->create($request, $student);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('student.dialogue.dialogue')]),
            'dialogue' => DialogueResource::make($dialogue),
        ]);
    }

    public function show(string $student, string $dialogue, DialogueService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        $dialogue = $service->findByUuidOrFail($student, $dialogue);

        $dialogue->load(['media', 'category', 'user', 'comments' => function ($q) {
            $q->with('user')->orderBy('created_at', 'desc');
        }]);

        return DialogueResource::make($dialogue);
    }

    public function update(DialogueRequest $request, string $student, string $dialogue, DialogueService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        $dialogue = $service->findByUuidOrFail($student, $dialogue);

        $service->update($request, $student, $dialogue);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.dialogue.dialogue')]),
        ]);
    }

    public function destroy(string $student, string $dialogue, DialogueService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        $dialogue = $service->findByUuidOrFail($student, $dialogue);

        $service->deletable($student, $dialogue);

        $dialogue->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('student.dialogue.dialogue')]),
        ]);
    }

    public function downloadMedia(string $student, string $dialogue, string $uuid, DialogueService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        $dialogue = $service->findByUuidOrFail($student, $dialogue);

        return $dialogue->downloadMedia($uuid);
    }
}
