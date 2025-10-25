<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Services\Academic\BookListListService;
use Illuminate\Http\Request;

class BookListExportController extends Controller
{
    public function __invoke(Request $request, BookListListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
