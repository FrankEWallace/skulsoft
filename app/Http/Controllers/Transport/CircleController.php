<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transport\CircleRequest;
use App\Http\Resources\Transport\CircleResource;
use App\Models\Transport\Circle;
use App\Services\Transport\CircleListService;
use App\Services\Transport\CircleService;
use Illuminate\Http\Request;

class CircleController extends Controller
{
    public function preRequisite(CircleService $service)
    {
        return response()->ok($service->preRequisite());
    }

    public function index(Request $request, CircleListService $service)
    {
        $this->authorize('viewAny', Circle::class);

        return $service->paginate($request);
    }

    public function store(CircleRequest $request, CircleService $service)
    {
        $this->authorize('create', Circle::class);

        $circle = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('transport.circle.circle')]),
            'circle' => CircleResource::make($circle),
        ]);
    }

    public function show(string $circle, CircleService $service): CircleResource
    {
        $circle = $service->findByUuidOrFail($circle);

        $this->authorize('view', $circle);

        return CircleResource::make($circle);
    }

    public function update(CircleRequest $request, string $circle, CircleService $service)
    {
        $circle = $service->findByUuidOrFail($circle);

        $this->authorize('update', $circle);

        $service->update($request, $circle);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('transport.circle.circle')]),
        ]);
    }

    public function destroy(string $circle, CircleService $service)
    {
        $circle = $service->findByUuidOrFail($circle);

        $this->authorize('delete', $circle);

        $service->deletable($circle);

        $circle->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('transport.circle.circle')]),
        ]);
    }
}
