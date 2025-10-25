<?php

namespace App\Http\Controllers\Form;

use App\Http\Controllers\Controller;
use App\Models\Form\Form;
use App\Services\Form\FormSubmissionListService;
use Illuminate\Http\Request;

class FormSubmissionExportController extends Controller
{
    public function __invoke(Request $request, string $form, FormSubmissionListService $service)
    {
        $form = Form::findByUuidOrFail($form);

        $this->authorize('view', $form);

        $list = $service->list($request, $form);

        return $service->export($list);
    }
}
