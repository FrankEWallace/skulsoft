<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Services\Library\BookWiseTransactionListService;
use Illuminate\Http\Request;

class BookWiseTransactionExportController extends Controller
{
    public function __invoke(Request $request, BookWiseTransactionListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
