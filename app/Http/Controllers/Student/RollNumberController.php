<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\RollNumberRequest;
use App\Services\Student\RollNumberService;
use Illuminate\Http\Request;

class RollNumberController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:student:edit|student-record:manage');
    }

    public function preRequisite(Request $request, RollNumberService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function fetch(Request $request, RollNumberService $service)
    {
        return $service->fetch($request);
    }

    public function store(RollNumberRequest $request, RollNumberService $service)
    {
        $service->store($request);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('student.roll_number.roll_number')]),
        ]);
    }
}
