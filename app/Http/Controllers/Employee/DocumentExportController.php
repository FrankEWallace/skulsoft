<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee\Employee;
use App\Services\Employee\DocumentListService;
use Illuminate\Http\Request;

class DocumentExportController extends Controller
{
    public function __invoke(Request $request, string $employee, DocumentListService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('view', $employee);

        $list = $service->list($request, $employee);

        return $service->export($list);
    }
}
