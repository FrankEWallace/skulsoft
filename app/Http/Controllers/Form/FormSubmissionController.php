<?php

namespace App\Http\Controllers\Form;

use App\Http\Controllers\Controller;
use App\Http\Resources\Form\FormSubmissionResource;
use App\Models\Form\Form;
use App\Models\Form\Submission;
use App\Services\Form\FormSubmissionListService;
use App\Services\Form\FormSubmissionService;
use Illuminate\Http\Request;

class FormSubmissionController extends Controller
{
    public function __construct()
    {
        //
    }

    public function index(Request $request, string $form, FormSubmissionListService $service)
    {
        $form = Form::findByUuidOrFail($form);

        return $service->paginate($request, $form);
    }

    public function show(Request $request, string $form, string $submission, FormSubmissionService $service)
    {
        $form = Form::findByUuidOrFail($form);

        $submission = $service->findByUuidOrFail($form, $submission);

        $submission->load('media');

        return FormSubmissionResource::make($submission);
    }

    public function destroy(string $form, string $submission, FormSubmissionService $service)
    {
        $form = Form::findByUuidOrFail($form);

        $submission = Submission::findByUuidOrFail($form, $submission);

        $service->delete($form, $submission);

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('form.submission.submission')]),
        ]);
    }

    public function downloadMedia(string $form, string $submission, string $uuid, FormSubmissionService $service)
    {
        $form = Form::findByUuidOrFail($form);

        $submission = Submission::findByUuidOrFail($form, $submission);

        $service->isMediaAccessible($form, $submission);

        return $submission->downloadMedia($uuid);
    }
}
