<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Academic\Subject;
use App\Services\Academic\SubjectActionService;
use Illuminate\Http\Request;

class SubjectActionController extends Controller
{
    public function updateFee(Request $request, string $subject, SubjectActionService $service)
    {
        $subject = Subject::findByUuidOrFail($subject);

        $this->authorize('update', $subject);

        $service->updateFee($request, $subject);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.subject.subject')]),
        ]);
    }

    public function reorder(Request $request, SubjectActionService $service)
    {
        $this->authorize('create', Subject::class);

        $menu = $service->reorder($request);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.subject.subject')]),
        ]);
    }
}
