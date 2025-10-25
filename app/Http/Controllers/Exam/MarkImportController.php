<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Services\Exam\MarkImportService;
use App\Services\Exam\MarkService;
use Illuminate\Http\Request;

class MarkImportController extends Controller
{
    public function __invoke(Request $request, MarkService $markService, MarkImportService $service)
    {
        $service->import($request);

        if (request()->boolean('validate')) {
            return response()->success([
                'message' => trans('general.data_validated'),
            ]);
        }

        return response()->success([
            'imported' => true,
            'message' => trans('global.imported', ['attribute' => trans('exam.mark')]),
        ]);
    }
}
