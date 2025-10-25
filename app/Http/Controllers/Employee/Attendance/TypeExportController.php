<?php

namespace App\Http\Controllers\Employee\Attendance;

use App\Http\Controllers\Controller;
use App\Services\Employee\Attendance\TypeListService as AttendanceTypeListService;
use Illuminate\Http\Request;

class TypeExportController extends Controller
{
    public function __invoke(Request $request, AttendanceTypeListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
