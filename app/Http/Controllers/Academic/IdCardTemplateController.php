<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\IdCardTemplateRequest;
use App\Http\Resources\Academic\IdCardTemplateResource;
use App\Models\Academic\IdCardTemplate;
use App\Services\Academic\IdCardTemplateListService;
use App\Services\Academic\IdCardTemplateService;
use Illuminate\Http\Request;

class IdCardTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, IdCardTemplateService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, IdCardTemplateListService $service)
    {
        $this->authorize('viewAny', IdCardTemplate::class);

        return $service->paginate($request);
    }

    public function store(IdCardTemplateRequest $request, IdCardTemplateService $service)
    {
        $this->authorize('create', IdCardTemplate::class);

        $idCardTemplate = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('academic.id_card.template.template')]),
            'id_card_template' => IdCardTemplateResource::make($idCardTemplate),
        ]);
    }

    public function show(string $idCardTemplate, IdCardTemplateService $service)
    {
        $idCardTemplate = IdCardTemplate::findByUuidOrFail($idCardTemplate);

        $this->authorize('view', $idCardTemplate);

        return IdCardTemplateResource::make($idCardTemplate);
    }

    public function export(Request $request, string $idCardTemplate, IdCardTemplateService $service)
    {
        $idCardTemplate = IdCardTemplate::findByUuidOrFail($idCardTemplate);

        $this->authorize('view', $idCardTemplate);

        return $service->export($idCardTemplate);
    }

    public function update(IdCardTemplateRequest $request, string $idCardTemplate, IdCardTemplateService $service)
    {
        $idCardTemplate = IdCardTemplate::findByUuidOrFail($idCardTemplate);

        $this->authorize('update', $idCardTemplate);

        $service->update($request, $idCardTemplate);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.id_card.template.template')]),
        ]);
    }

    public function destroy(string $idCardTemplate, IdCardTemplateService $service)
    {
        $idCardTemplate = IdCardTemplate::findByUuidOrFail($idCardTemplate);

        $this->authorize('delete', $idCardTemplate);

        $service->deletable($idCardTemplate);

        $idCardTemplate->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('academic.id_card.template.template')]),
        ]);
    }
}
