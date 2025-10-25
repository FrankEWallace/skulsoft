<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Academic\Subject;
use App\Services\Academic\SubjectRecordListService;
use Illuminate\Http\Request;

class SubjectRecordExportController extends Controller
{
    public function __invoke(Request $request, string $subject, SubjectRecordListService $service)
    {
        $subject = Subject::findByUuidOrFail($subject);

        $list = $service->list($request, $subject);

        return $service->export($list);
    }
}
