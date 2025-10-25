<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Services\Reception\EnquiryImportService;
use Illuminate\Http\Request;

class EnquiryImportController extends Controller
{
    public function __invoke(Request $request, EnquiryImportService $service)
    {
        $service->import($request);

        if (request()->boolean('validate')) {
            return response()->success([
                'message' => trans('general.data_validated'),
            ]);
        }

        return response()->success([
            'imported' => true,
            'message' => trans('global.imported', ['attribute' => trans('reception.enquiry.enquiry')]),
        ]);
    }
}
