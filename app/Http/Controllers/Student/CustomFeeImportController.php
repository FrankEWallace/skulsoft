<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\Student\CustomFeeImportService;
use Illuminate\Http\Request;

class CustomFeeImportController extends Controller
{
    public function __invoke(Request $request, CustomFeeImportService $service)
    {
        $service->import($request);

        if (request()->boolean('validate')) {
            return response()->success([
                'message' => trans('general.data_validated'),
            ]);
        }

        return response()->success([
            'imported' => true,
            'message' => trans('global.imported', ['attribute' => trans('student.fee.fee')]),
        ]);
    }
}
