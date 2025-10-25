<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;
use App\Services\Library\BookAdditionListService;
use Illuminate\Http\Request;

class BookAdditionExportController extends Controller
{
    public function __invoke(Request $request, BookAdditionListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
