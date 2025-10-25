<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reception\CorrespondenceRequest;
use App\Http\Resources\Reception\CorrespondenceResource;
use App\Models\Reception\Correspondence;
use App\Services\Reception\CorrespondenceListService;
use App\Services\Reception\CorrespondenceService;
use Illuminate\Http\Request;

class CorrespondenceController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, CorrespondenceService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, CorrespondenceListService $service)
    {
        $this->authorize('viewAny', Correspondence::class);

        return $service->paginate($request);
    }

    public function store(CorrespondenceRequest $request, CorrespondenceService $service)
    {
        $this->authorize('create', Correspondence::class);

        $correspondence = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('reception.correspondence.correspondence')]),
            'correspondence' => CorrespondenceResource::make($correspondence),
        ]);
    }

    public function show(Correspondence $correspondence, CorrespondenceService $service)
    {
        $this->authorize('view', $correspondence);

        $correspondence->load('reference', 'media');

        return CorrespondenceResource::make($correspondence);
    }

    public function update(CorrespondenceRequest $request, Correspondence $correspondence, CorrespondenceService $service)
    {
        $this->authorize('update', $correspondence);

        $service->update($request, $correspondence);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('reception.correspondence.correspondence')]),
        ]);
    }

    public function destroy(Correspondence $correspondence, CorrespondenceService $service)
    {
        $this->authorize('delete', $correspondence);

        $service->deletable($correspondence);

        $correspondence->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('reception.correspondence.correspondence')]),
        ]);
    }

    public function downloadMedia(Correspondence $correspondence, string $uuid, CorrespondenceService $service)
    {
        $this->authorize('view', $correspondence);

        return $correspondence->downloadMedia($uuid);
    }
}
