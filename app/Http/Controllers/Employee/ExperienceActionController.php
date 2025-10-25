<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee\Employee;
use App\Services\Employee\ExperienceActionService;
use Illuminate\Http\Request;

class ExperienceActionController extends Controller
{
    public function action(Request $request, string $employee, string $experience, ExperienceActionService $service)
    {
        $employee = Employee::findSummaryByUuidOrFail($employee);

        $this->authorize('selfServiceAction', $employee);

        $service->action($request, $employee, $experience);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('employee.experience.experience')]),
        ]);
    }
}
