<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transport\StoppageRequest;
use App\Http\Resources\Transport\StoppageResource;
use App\Services\Transport\StoppageListService;
use App\Services\Transport\StoppageService;
use Illuminate\Http\Request;

class StoppageController extends Controller
{
    public function preRequisite(StoppageService $service)
    {
        return response()->ok($service->preRequisite());
    }

    public function index(Request $request, StoppageListService $service)
    {
        return $service->paginate($request);
    }

    public function store(StoppageRequest $request, StoppageService $service)
    {
        $stoppage = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('transport.stoppage.stoppage')]),
            'stoppage' => StoppageResource::make($stoppage),
        ]);
    }

    public function show(string $stoppage, StoppageService $service): StoppageResource
    {
        $stoppage = $service->findByUuidOrFail($stoppage);

        return StoppageResource::make($stoppage);
    }

    public function update(StoppageRequest $request, string $stoppage, StoppageService $service)
    {
        $stoppage = $service->findByUuidOrFail($stoppage);

        $service->update($request, $stoppage);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('transport.stoppage.stoppage')]),
        ]);
    }

    public function destroy(string $stoppage, StoppageService $service)
    {
        $stoppage = $service->findByUuidOrFail($stoppage);

        $service->deletable($stoppage);

        $stoppage->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('transport.stoppage.stoppage')]),
        ]);
    }
}
