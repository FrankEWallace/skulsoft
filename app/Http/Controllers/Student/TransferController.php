<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\TransferRequest;
use App\Http\Resources\Student\TransferResource;
use App\Models\Student\Student;
use App\Services\Student\TransferListService;
use App\Services\Student\TransferService;
use Illuminate\Http\Request;

class TransferController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, TransferService $service)
    {
        $this->authorize('transfer', Student::class);

        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, TransferListService $service)
    {
        $this->authorize('transfer', Student::class);

        return $service->paginate($request);
    }

    public function store(TransferRequest $request, TransferService $service)
    {
        $this->authorize('transfer', Student::class);

        $service->create($request);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.student')]),
        ]);
    }

    public function show(string $student, TransferService $service)
    {
        $student = Student::findTransferredByUuidOrFail($student);

        $this->authorize('transfer', Student::class);

        $student->load('admission.media');

        return TransferResource::make($student);
    }

    public function update(TransferRequest $request, string $student, TransferService $service)
    {
        $student = Student::findTransferredByUuidOrFail($student);

        $this->authorize('transfer', Student::class);

        $service->update($request, $student);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.student')]),
        ]);
    }

    public function destroy(string $student, TransferService $service)
    {
        $student = Student::findTransferredByUuidOrFail($student);

        $this->authorize('transfer', Student::class);

        $service->deletable($student);

        $service->delete($student);

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('student.student')]),
        ]);
    }

    public function downloadMedia(string $student, string $uuid, TransferService $service)
    {
        $student = Student::findTransferredByUuidOrFail($student);

        $this->authorize('transfer', Student::class);

        return $student->admission->downloadMedia($uuid);
    }
}
