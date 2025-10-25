<?php

namespace App\Http\Controllers\Form;

use App\Http\Controllers\Controller;
use App\Http\Requests\Form\FormRequest;
use App\Http\Resources\Form\FormDetailResource;
use App\Http\Resources\Form\FormResource;
use App\Models\Form\Form;
use App\Services\Form\FormListService;
use App\Services\Form\FormService;
use Illuminate\Http\Request;

class FormController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, FormService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, FormListService $service)
    {
        $this->authorize('viewAny', Form::class);

        return $service->paginate($request);
    }

    public function store(FormRequest $request, FormService $service)
    {
        $this->authorize('create', Form::class);

        $form = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('form.form')]),
            'form' => FormResource::make($form),
        ]);
    }

    public function show(string $form, FormService $service)
    {
        $form = Form::findByUuidOrFail($form);

        $this->authorize('view', $form);

        $form->load('fields', 'audiences.audienceable', 'media');

        return FormResource::make($form);
    }

    public function detail(string $form, FormService $service)
    {
        $form = Form::findByUuidOrFail($form);

        $this->authorize('view', $form);

        $form->load('audiences.audienceable');

        $form->can_submit = $service->canSubmit($form);

        $form->load(['fields', 'media', 'submissions' => function ($query) {
            $query->where('user_id', auth()->id());
        }]);

        return FormDetailResource::make($form);
    }

    public function update(FormRequest $request, string $form, FormService $service)
    {
        $form = Form::findByUuidOrFail($form);

        $this->authorize('update', $form);

        $service->update($request, $form);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('form.form')]),
        ]);
    }

    public function destroy(string $form, FormService $service)
    {
        $form = Form::findByUuidOrFail($form);

        $this->authorize('delete', $form);

        $service->deletable($form);

        $form->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('form.form')]),
        ]);
    }

    public function downloadMedia(string $form, string $uuid, FormService $service)
    {
        $form = Form::findByUuidOrFail($form);

        $this->authorize('view', $form);

        return $form->downloadMedia($uuid);
    }
}
