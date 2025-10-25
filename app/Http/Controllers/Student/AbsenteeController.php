<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student\Student;
use App\Services\Student\AbsenteeListService;
use App\Services\Student\AbsenteeService;
use Illuminate\Http\Request;

class AbsenteeController extends Controller
{
    public function preRequisite(Request $request, AbsenteeService $service)
    {
        $this->authorize('listAttendance', Student::class);

        return response()->ok($service->preRequisite($request));
    }

    public function fetch(Request $request, AbsenteeListService $service)
    {
        $this->authorize('listAttendance', Student::class);

        return [];
        // return $service->paginate($request);
    }
}
