<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Services\Library\BookCopyListService;
use Illuminate\Http\Request;

class BookCopyExportController extends Controller
{
    public function __invoke(Request $request, BookCopyListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
