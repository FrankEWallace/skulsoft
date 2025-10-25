<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Http\Resources\Exam\FormResource;
use App\Models\Exam\Form;
use App\Services\Exam\FormListService;
use App\Services\Exam\FormService;
use Illuminate\Http\Request;

class FormController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
        $this->middleware('permission:exam-form:manage');
    }

    public function preRequisite(Request $request, FormService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, FormListService $service)
    {
        return $service->paginate($request);
    }

    public function show(string $form, FormService $service)
    {
        $form = Form::findByUuidOrFail($form);

        return FormResource::make($form);
    }

    public function destroy(string $form, FormService $service)
    {
        $form = Form::findByUuidOrFail($form);

        $service->deletable($form);

        $service->delete($form);

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('exam.form.form')]),
        ]);
    }
}
