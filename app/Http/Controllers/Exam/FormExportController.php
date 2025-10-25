<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Services\Exam\FormListService;
use Illuminate\Http\Request;

class FormExportController extends Controller
{
    public function __invoke(Request $request, FormListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
