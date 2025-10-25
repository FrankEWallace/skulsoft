<?php

namespace App\Http\Controllers\Resource;

use App\Actions\UpdateViewLog;
use App\Http\Controllers\Controller;
use App\Http\Requests\Resource\LearningMaterialRequest;
use App\Http\Resources\Resource\LearningMaterialResource;
use App\Models\Resource\LearningMaterial;
use App\Services\Resource\LearningMaterialListService;
use App\Services\Resource\LearningMaterialService;
use Illuminate\Http\Request;

class LearningMaterialController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, LearningMaterialService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, LearningMaterialListService $service)
    {
        $this->authorize('viewAny', LearningMaterial::class);

        return $service->paginate($request);
    }

    public function store(LearningMaterialRequest $request, LearningMaterialService $service)
    {
        $this->authorize('create', LearningMaterial::class);

        $learningMaterial = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('resource.learning_material.learning_material')]),
            'learning_material' => LearningMaterialResource::make($learningMaterial),
        ]);
    }

    public function show(Request $request, string $learningMaterial, LearningMaterialService $service)
    {
        $learningMaterial = LearningMaterial::findByUuidOrFail($learningMaterial);

        $this->authorize('view', $learningMaterial);

        (new UpdateViewLog)->handle($learningMaterial);

        if (auth()->user()->can('learning-material:view-log')) {
            $learningMaterial->load('viewLogs');
        }

        $learningMaterial->load(['records.subject', 'records.batch.course', 'employee' => fn ($q) => $q->summary(), 'media']);

        return LearningMaterialResource::make($learningMaterial);
    }

    public function update(LearningMaterialRequest $request, string $learningMaterial, LearningMaterialService $service)
    {
        $learningMaterial = LearningMaterial::findByUuidOrFail($learningMaterial);

        $this->authorize('update', $learningMaterial);

        $service->update($request, $learningMaterial);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('resource.learning_material.learning_material')]),
        ]);
    }

    public function destroy(string $learningMaterial, LearningMaterialService $service)
    {
        $learningMaterial = LearningMaterial::findByUuidOrFail($learningMaterial);

        $this->authorize('delete', $learningMaterial);

        $service->deletable($learningMaterial);

        $learningMaterial->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('resource.learning_material.learning_material')]),
        ]);
    }

    public function downloadMedia(string $learningMaterial, string $uuid, LearningMaterialService $service)
    {
        $learningMaterial = LearningMaterial::findByUuidOrFail($learningMaterial);

        $this->authorize('view', $learningMaterial);

        return $learningMaterial->downloadMedia($uuid);
    }
}
