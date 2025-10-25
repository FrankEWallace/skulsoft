<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transport\RouteEmployeeRequest;
use App\Http\Requests\Transport\RouteStudentRequest;
use App\Models\Transport\Route;
use App\Services\Transport\RouteActionService;
use Illuminate\Http\Request;

class RouteActionController extends Controller
{
    public function addStudent(RouteStudentRequest $request, string $route, RouteActionService $service)
    {
        $route = Route::findByUuidOrFail($route);

        $this->authorize('update', $route);

        $service->addStudent($request, $route);

        return response()->success([
            'message' => trans('global.added', ['attribute' => trans('student.student')]),
        ]);
    }

    public function addEmployee(RouteEmployeeRequest $request, string $route, RouteActionService $service)
    {
        $route = Route::findByUuidOrFail($route);

        $this->authorize('update', $route);

        $service->addEmployee($request, $route);

        return response()->success([
            'message' => trans('global.added', ['attribute' => trans('employee.employee')]),
        ]);
    }

    public function removePassenger(Request $request, string $route, string $uuid, RouteActionService $service)
    {
        $route = Route::findByUuidOrFail($route);

        $this->authorize('update', $route);

        $service->removePassenger($request, $route, $uuid);

        return response()->success([
            'message' => trans('global.removed', ['attribute' => trans('transport.route.passenger')]),
        ]);
    }
}
