<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Services\Library\BookListService;
use Illuminate\Http\Request;

class BookExportController extends Controller
{
    public function __invoke(Request $request, BookListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
