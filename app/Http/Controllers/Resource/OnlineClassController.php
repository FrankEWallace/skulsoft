<?php

namespace App\Http\Controllers\Resource;

use App\Http\Controllers\Controller;
use App\Http\Requests\Resource\OnlineClassRequest;
use App\Http\Resources\Resource\OnlineClassResource;
use App\Models\Resource\OnlineClass;
use App\Services\Resource\OnlineClassListService;
use App\Services\Resource\OnlineClassService;
use Illuminate\Http\Request;

class OnlineClassController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, OnlineClassService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, OnlineClassListService $service)
    {
        $this->authorize('viewAny', OnlineClass::class);

        return $service->paginate($request);
    }

    public function store(OnlineClassRequest $request, OnlineClassService $service)
    {
        $this->authorize('create', OnlineClass::class);

        $onlineClass = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('resource.online_class.online_class')]),
            'online_class' => OnlineClassResource::make($onlineClass),
        ]);
    }

    public function show(Request $request, string $onlineClass, OnlineClassService $service)
    {
        $onlineClass = OnlineClass::findByUuidOrFail($onlineClass);

        $this->authorize('view', $onlineClass);

        $onlineClass->load(['records.subject', 'records.batch.course', 'employee' => fn ($q) => $q->summary(), 'media']);

        return OnlineClassResource::make($onlineClass);
    }

    public function update(OnlineClassRequest $request, string $onlineClass, OnlineClassService $service)
    {
        $onlineClass = OnlineClass::findByUuidOrFail($onlineClass);

        $this->authorize('update', $onlineClass);

        $service->update($request, $onlineClass);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('resource.online_class.online_class')]),
        ]);
    }

    public function destroy(string $onlineClass, OnlineClassService $service)
    {
        $onlineClass = OnlineClass::findByUuidOrFail($onlineClass);

        $this->authorize('delete', $onlineClass);

        $service->deletable($onlineClass);

        $onlineClass->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('resource.online_class.online_class')]),
        ]);
    }

    public function downloadMedia(string $onlineClass, string $uuid, OnlineClassService $service)
    {
        $onlineClass = OnlineClass::findByUuidOrFail($onlineClass);

        $this->authorize('view', $onlineClass);

        return $onlineClass->downloadMedia($uuid);
    }
}
