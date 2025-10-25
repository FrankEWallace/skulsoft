<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Services\Exam\OnlineExamListService;
use Illuminate\Http\Request;

class OnlineExamExportController extends Controller
{
    public function __invoke(Request $request, OnlineExamListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
