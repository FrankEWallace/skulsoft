<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\TermRequest;
use App\Http\Resources\Exam\TermResource;
use App\Models\Exam\Term;
use App\Services\Exam\TermListService;
use App\Services\Exam\TermService;
use Illuminate\Http\Request;

class TermController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, TermService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, TermListService $service)
    {
        return $service->paginate($request);
    }

    public function store(TermRequest $request, TermService $service)
    {
        $term = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('exam.term.term')]),
            'term' => TermResource::make($term),
        ]);
    }

    public function show(Term $term, TermService $service)
    {
        $term->load('division');

        return TermResource::make($term);
    }

    public function update(TermRequest $request, Term $term, TermService $service)
    {
        $service->update($request, $term);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('exam.term.term')]),
        ]);
    }

    public function destroy(Term $term, TermService $service)
    {
        $service->deletable($term);

        $term->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('exam.term.term')]),
        ]);
    }
}
