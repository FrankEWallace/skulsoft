<?php

namespace App\Http\Controllers;

use App\Services\Employee\EmployeeListService;
use App\Services\Student\StudentListService;
use Illuminate\Http\Request;

class Search extends Controller
{
    /**
     * Search items
     */
    public function __invoke(Request $request, StudentListService $studentService, EmployeeListService $employeeService)
    {
        $request->merge(['with_transferred' => true]);

        $students = $studentService->paginate($request);

        $request->merge(['type' => 'all']);

        $employees = $employeeService->paginate($request);

        return compact('students', 'employees');
    }
}
