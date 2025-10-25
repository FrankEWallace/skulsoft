<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\ClassTimingRequest;
use App\Http\Resources\Academic\ClassTimingResource;
use App\Models\Academic\ClassTiming;
use App\Services\Academic\ClassTimingListService;
use App\Services\Academic\ClassTimingService;
use Illuminate\Http\Request;

class ClassTimingController extends Controller
{
    public function preRequisite(ClassTimingService $service)
    {
        return response()->ok($service->preRequisite());
    }

    public function index(Request $request, ClassTimingListService $service)
    {
        $this->authorize('viewAny', ClassTiming::class);

        return $service->paginate($request);
    }

    public function store(ClassTimingRequest $request, ClassTimingService $service)
    {
        $this->authorize('create', ClassTiming::class);

        $classTiming = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('academic.class_timing.class_timing')]),
            'class_timing' => ClassTimingResource::make($classTiming),
        ]);
    }

    public function show(string $classTiming, ClassTimingService $service): ClassTimingResource
    {
        $classTiming = ClassTiming::findByUuidOrFail($classTiming);

        $this->authorize('view', $classTiming);

        $classTiming->load('sessions');

        return ClassTimingResource::make($classTiming);
    }

    public function update(ClassTimingRequest $request, string $classTiming, ClassTimingService $service)
    {
        $classTiming = ClassTiming::findByUuidOrFail($classTiming);

        $this->authorize('update', $classTiming);

        $service->update($request, $classTiming);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.class_timing.class_timing')]),
        ]);
    }

    public function destroy(string $classTiming, ClassTimingService $service)
    {
        $classTiming = ClassTiming::findByUuidOrFail($classTiming);

        $this->authorize('delete', $classTiming);

        $service->deletable($classTiming);

        $classTiming->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('academic.class_timing.class_timing')]),
        ]);
    }
}
