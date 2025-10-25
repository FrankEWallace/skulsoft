<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Services\Exam\ExamListService;
use Illuminate\Http\Request;

class ExamExportController extends Controller
{
    public function __invoke(Request $request, ExamListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
