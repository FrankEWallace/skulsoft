<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\ObservationMarkRequest;
use App\Services\Exam\ObservationMarkService;
use Illuminate\Http\Request;

class ObservationMarkController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:exam-schedule:read')->only(['preRequisite', 'fetch']);
        $this->middleware('permission:exam:marks-record|exam:subject-incharge-wise-marks-record')->only(['store', 'remove']);
    }

    public function preRequisite(Request $request, ObservationMarkService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function fetch(Request $request, ObservationMarkService $service)
    {
        return $service->fetch($request);
    }

    public function store(ObservationMarkRequest $request, ObservationMarkService $service)
    {
        $service->store($request);

        return response()->success([
            'message' => trans('global.stored', ['attribute' => trans('exam.observation_mark')]),
        ]);
    }

    public function remove(Request $request, ObservationMarkService $service)
    {
        $service->remove($request);

        return response()->success([
            'message' => trans('global.removed', ['attribute' => trans('exam.observation_mark')]),
        ]);
    }
}
