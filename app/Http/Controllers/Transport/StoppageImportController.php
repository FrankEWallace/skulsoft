<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\Controller;
use App\Services\Transport\StoppageImportService;
use Illuminate\Http\Request;

class StoppageImportController extends Controller
{
    public function __invoke(Request $request, StoppageImportService $service)
    {
        $service->import($request);

        if (request()->boolean('validate')) {
            return response()->success([
                'message' => trans('general.data_validated'),
            ]);
        }

        return response()->success([
            'imported' => true,
            'message' => trans('global.imported', ['attribute' => trans('transport.stoppage.stoppage')]),
        ]);
    }
}
