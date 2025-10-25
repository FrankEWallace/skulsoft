<?php

namespace App\Http\Controllers\Form;

use App\Http\Controllers\Controller;
use App\Models\Form\Form;
use App\Services\Form\FormActionService;
use Illuminate\Http\Request;

class FormActionController extends Controller
{
    public function updateStatus(Request $request, string $form, FormActionService $service)
    {
        $form = Form::findByUuidOrFail($form);

        $this->authorize('update', $form);

        $form = $service->updateStatus($request, $form);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('form.form')]),
        ]);
    }
}
