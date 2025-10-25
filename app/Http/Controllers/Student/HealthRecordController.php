<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\HealthRecordRequest;
use App\Services\Student\HealthRecordService;
use Illuminate\Http\Request;

class HealthRecordController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:student:edit|student-record:manage');
    }

    public function preRequisite(Request $request, HealthRecordService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function fetch(Request $request, HealthRecordService $service)
    {
        return $service->fetch($request);
    }

    public function store(HealthRecordRequest $request, HealthRecordService $service)
    {
        $service->store($request);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.health_record.health_record')]),
        ]);
    }
}
