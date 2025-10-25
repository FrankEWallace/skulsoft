<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee\Employee;
use App\Services\Employee\QualificationActionService;
use Illuminate\Http\Request;

class QualificationActionController extends Controller
{
    public function action(Request $request, string $employee, string $qualification, QualificationActionService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('selfServiceAction', $employee);

        $service->action($request, $employee, $qualification);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('employee.qualification.qualification')]),
        ]);
    }
}
