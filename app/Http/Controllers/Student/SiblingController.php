<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student\Student;
use App\Services\Student\SiblingListService;
use App\Services\Student\SiblingService;
use Illuminate\Http\Request;

class SiblingController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, string $student, SiblingService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('update', $student);

        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, string $student, SiblingListService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        return $service->paginate($request, $student);
    }

    public function export(Request $request, SiblingService $service)
    {
        $parents = $service->export($request);

        return view('print.student.siblings', compact('parents'));
    }
}
