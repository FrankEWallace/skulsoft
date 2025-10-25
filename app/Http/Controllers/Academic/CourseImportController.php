<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Services\Academic\CourseImportService;
use Illuminate\Http\Request;

class CourseImportController extends Controller
{
    public function __invoke(Request $request, CourseImportService $service)
    {
        $service->import($request);

        if (request()->boolean('validate')) {
            return response()->success([
                'message' => trans('general.data_validated'),
            ]);
        }

        return response()->success([
            'imported' => true,
            'message' => trans('global.imported', ['attribute' => trans('academic.course.course')]),
        ]);
    }
}
