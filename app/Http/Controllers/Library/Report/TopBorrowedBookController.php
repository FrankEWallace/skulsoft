<?php

namespace App\Http\Controllers\Library\Report;

use App\Http\Controllers\Controller;
use App\Services\Library\Report\TopBorrowedBookListService;
use App\Services\Library\Report\TopBorrowedBookService;
use Illuminate\Http\Request;

class TopBorrowedBookController extends Controller
{
    public function preRequisite(Request $request, TopBorrowedBookService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function fetch(Request $request, TopBorrowedBookListService $service)
    {
        return $service->paginate($request);
    }
}
