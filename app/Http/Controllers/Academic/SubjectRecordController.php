<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\SubjectRecordRequest;
use App\Http\Resources\Academic\SubjectRecordResource;
use App\Models\Academic\Subject;
use App\Models\Academic\SubjectRecord;
use App\Services\Academic\SubjectRecordListService;
use App\Services\Academic\SubjectRecordService;
use Illuminate\Http\Request;

class SubjectRecordController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function index(Request $request, string $subject, SubjectRecordListService $service)
    {
        $subject = Subject::findByUuidOrFail($subject);

        $this->authorize('view', $subject);

        return $service->paginate($request, $subject);
    }

    public function store(SubjectRecordRequest $request, string $subject, SubjectRecordService $service)
    {
        $subject = Subject::findByUuidOrFail($subject);

        $this->authorize('update', $subject);

        $subjectRecord = $service->create($request, $subject);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('academic.subject.record')]),
            'subject_record' => SubjectRecordResource::make($subjectRecord),
        ]);
    }

    public function show(Request $request, string $subject, string $subjectRecord, SubjectRecordService $service)
    {
        $subject = Subject::findByUuidOrFail($subject);

        $this->authorize('view', $subject);

        $subjectRecord = SubjectRecord::findByUuidOrFail($subject->id, $subjectRecord);

        return SubjectRecordResource::make($subjectRecord);
    }

    public function update(SubjectRecordRequest $request, string $subject, string $subjectRecord, SubjectRecordService $service)
    {
        $subject = Subject::findByUuidOrFail($subject);

        $this->authorize('update', $subject);

        $subjectRecord = SubjectRecord::findByUuidOrFail($subject->id, $subjectRecord);

        $service->update($request, $subject, $subjectRecord);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.subject.record')]),
        ]);
    }

    public function destroy(string $subject, string $subjectRecord, SubjectRecordService $service)
    {
        $subject = Subject::findByUuidOrFail($subject);

        $this->authorize('update', $subject);

        $subjectRecord = SubjectRecord::findByUuidOrFail($subject->id, $subjectRecord);

        $service->deletable($subject, $subjectRecord);

        $subjectRecord->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('academic.subject.record')]),
        ]);
    }
}
