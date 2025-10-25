<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\RecordRequest;
use App\Models\Student\Student;
use App\Services\Student\RecordListService;
use App\Services\Student\RecordService;
use Illuminate\Http\Request;

class RecordController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, string $student, RecordService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('update', $student);

        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, string $student, RecordListService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        return $service->paginate($request, $student);
    }

    // public function store(RecordRequest $request, string $student, RecordService $service)
    // {
    //     $student = Student::findByUuidOrFail($student);

    //     $this->authorize('update', $student);

    //     $this->authorize('create', Record::class);

    //     $record = $service->create($request, $student);

    //     return response()->success([
    //         'message' => trans('global.created', ['attribute' => trans('student.record.record')]),
    //         'guardian' => RecordResource::make($record),
    //     ]);
    // }

    // public function show(string $student, string $record, RecordService $service)
    // {
    //     $student = Student::findByUuidOrFail($student);

    //     $this->authorize('view', $student);

    //     $record = Record::query()
    //         ->with('contact')
    //         ->filterByPrimaryContact($student->contact_id)
    //         ->findByUuidOrFail($record);

    //     return RecordResource::make($record);
    // }

    public function update(RecordRequest $request, string $student, string $record, RecordService $service)
    {
        $student = Student::findSummaryByUuidOrFail($student);

        $this->authorize('manageRecord', $student);

        $record = Student::findByUuidOrFail($record);

        $service->update($request, $student, $record);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.record.record')]),
        ]);
    }

    public function cancelAdmission(Request $request, string $student, RecordService $service)
    {
        $student = Student::findSummaryByUuidOrFail($student);

        $this->authorize('manageRecord', $student);

        $service->cancelAdmission($request, $student);

        return response()->success([
            'message' => trans('global.cancelled', ['attribute' => trans('student.admission.admission')]),
        ]);
    }

    public function cancelPromotion(Request $request, string $student, RecordService $service)
    {
        $student = Student::findSummaryByUuidOrFail($student);

        $this->authorize('manageRecord', $student);

        $service->cancelPromotion($request, $student);

        return response()->success([
            'message' => trans('global.cancelled', ['attribute' => trans('student.promotion.promotion')]),
        ]);
    }

    // public function destroy(string $student, string $record, RecordService $service)
    // {
    //     $student = Student::findByUuidOrFail($student);

    //     $this->authorize('update', $student);

    //     $record = Record::query()
    //         ->with('contact')
    //         ->filterByPrimaryContact($student->contact_id)
    //         ->findByUuidOrFail($record);

    //     $this->authorize('delete', $record);

    //     $service->deletable($student, $record);

    //     $record->delete();

    //     return response()->success([
    //         'message' => trans('global.deleted', ['attribute' => trans('student.record.record')]),
    //     ]);
    // }
}
