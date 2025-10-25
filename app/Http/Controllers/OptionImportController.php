<?php

namespace App\Http\Controllers;

use App\Services\OptionImportService;
use Illuminate\Http\Request;

class OptionImportController extends Controller
{
    public function __invoke(Request $request, OptionImportService $service)
    {
        $service->import($request);

        if (request()->boolean('validate')) {
            return response()->success([
                'message' => trans('general.data_validated'),
            ]);
        }

        return response()->success([
            'imported' => true,
            'message' => trans('global.imported', ['attribute' => $request->trans]),
        ]);
    }
}
