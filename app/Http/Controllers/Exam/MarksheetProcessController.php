<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Services\Exam\MarksheetProcessService;
use Illuminate\Http\Request;

class MarksheetProcessController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:exam-marksheet:generate')->only(['generate']);
    }

    public function preRequisite(Request $request, MarksheetProcessService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function process(Request $request, MarksheetProcessService $service)
    {
        $service->process($request);

        return response()->success([
            'message' => trans('global.processed', ['attribute' => trans('exam.marksheet.marksheet')]),
        ]);
    }
}
