<?php

namespace App\Http\Controllers\Library\Report;

use App\Http\Controllers\Controller;
use App\Services\Library\Report\TopBorrowedBookListService;
use Illuminate\Http\Request;

class TopBorrowedBookExportController extends Controller
{
    public function __invoke(Request $request, TopBorrowedBookListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
