<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Services\Library\BookImportService;
use Illuminate\Http\Request;

class BookImportController extends Controller
{
    public function __invoke(Request $request, BookImportService $service)
    {
        $service->import($request);

        if (request()->boolean('validate')) {
            return response()->success([
                'message' => trans('general.data_validated'),
            ]);
        }

        return response()->success([
            'imported' => true,
            'message' => trans('global.imported', ['attribute' => trans('library.book.book')]),
        ]);
    }
}
