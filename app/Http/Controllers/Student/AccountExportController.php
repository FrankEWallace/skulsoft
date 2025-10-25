<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student\Student;
use App\Services\Student\AccountListService;
use Illuminate\Http\Request;

class AccountExportController extends Controller
{
    public function __invoke(Request $request, string $student, AccountListService $service)
    {
        $student = Student::findByUuidOrFail($student);

        $this->authorize('view', $student);

        $list = $service->list($request, $student);

        return $service->export($list);
    }
}
