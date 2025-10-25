<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Models\Exam\Term;
use App\Services\Exam\TermActionService;
use Illuminate\Http\Request;

class TermActionController extends Controller
{
    public function reorder(Request $request, TermActionService $service)
    {
        $this->authorize('create', Term::class);

        $menu = $service->reorder($request);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('exam.term.term')]),
        ]);
    }
}
