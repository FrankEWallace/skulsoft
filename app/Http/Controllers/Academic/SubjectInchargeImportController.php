<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Services\Academic\SubjectInchargeImportService;
use Illuminate\Http\Request;

class SubjectInchargeImportController extends Controller
{
    public function __invoke(Request $request, SubjectInchargeImportService $service)
    {
        $service->import($request);

        if (request()->boolean('validate')) {
            return response()->success([
                'message' => trans('general.data_validated'),
            ]);
        }

        return response()->success([
            'imported' => true,
            'message' => trans('global.imported', ['attribute' => trans('academic.subject_incharge.subject_incharge')]),
        ]);
    }
}
