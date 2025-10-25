<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee\Employee;
use App\Services\Employee\DocumentActionService;
use Illuminate\Http\Request;

class DocumentActionController extends Controller
{
    public function action(Request $request, string $employee, string $document, DocumentActionService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('selfServiceAction', $employee);

        $service->action($request, $employee, $document);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('employee.document.document')]),
        ]);
    }
}
