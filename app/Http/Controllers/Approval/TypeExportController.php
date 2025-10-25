<?php

namespace App\Http\Controllers\Approval;

use App\Http\Controllers\Controller;
use App\Services\Approval\TypeListService;
use Illuminate\Http\Request;

class TypeExportController extends Controller
{
    public function __invoke(Request $request, TypeListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
