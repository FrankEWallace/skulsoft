<?php

namespace App\Http\Controllers\Form;

use App\Http\Controllers\Controller;
use App\Http\Requests\Form\FormSubmitRequest;
use App\Models\Form\Form;
use App\Services\Form\FormSubmitService;

class FormSubmitController extends Controller
{
    public function __construct()
    {
        //
    }

    public function __invoke(FormSubmitRequest $request, string $form, FormSubmitService $service)
    {
        $form = Form::findByUuidOrFail($form);

        $this->authorize('submit', $form);

        $service->submit($request, $form);

        return response()->success([
            'message' => trans('form.submitted'),
        ]);
    }
}
