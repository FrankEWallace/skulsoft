<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee\Employee;
use App\Services\Employee\InchargeListService;
use Illuminate\Http\Request;

class InchargeController extends Controller
{
    public function index(Request $request, string $employee, InchargeListService $service)
    {
        $employee = Employee::findDetailByUuidOrFail($employee);

        $this->authorize('view', $employee);

        return $service->paginate($request, $employee);
    }
}
