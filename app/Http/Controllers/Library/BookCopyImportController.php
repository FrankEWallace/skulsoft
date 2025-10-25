<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Services\Library\BookCopyImportService;
use Illuminate\Http\Request;

class BookCopyImportController extends Controller
{
    public function __invoke(Request $request, BookCopyImportService $service)
    {
        $service->import($request);

        if (request()->boolean('validate')) {
            return response()->success([
                'message' => trans('general.data_validated'),
            ]);
        }

        return response()->success([
            'imported' => true,
            'message' => trans('global.imported', ['attribute' => trans('library.book_copy.book_copy')]),
        ]);
    }
}
