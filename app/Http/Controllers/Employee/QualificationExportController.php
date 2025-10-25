<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee\Employee;
use App\Services\Employee\QualificationListService;
use Illuminate\Http\Request;

class QualificationExportController extends Controller
{
    public function __invoke(Request $request, string $employee, QualificationListService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('view', $employee);

        $list = $service->list($request, $employee);

        return $service->export($list);
    }
}
