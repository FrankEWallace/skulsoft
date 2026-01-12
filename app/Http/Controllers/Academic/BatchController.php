<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\BatchRequest;
use App\Http\Resources\Academic\BatchResource;
use App\Domain\Academic\Models\Batch;
use App\Services\Academic\BatchListService;
use App\Services\Academic\BatchService;
use Illuminate\Http\Request;

class BatchController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, BatchService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, BatchListService $service)
    {
        $this->authorize('viewAny', Batch::class);

        return $service->paginate($request);
    }

    public function store(BatchRequest $request, BatchService $service)
    {
        $this->authorize('create', Batch::class);

        $batch = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('academic.batch.batch')]),
            'batch' => BatchResource::make($batch),
        ]);
    }

    public function show(Request $request, string $batch, BatchService $service)
    {
        $batch = Batch::findByUuidOrFail($batch);

        $this->authorize('view', $batch);

        $batch->load('course');

        $request->merge([
            'details' => true,
        ]);

        return BatchResource::make($batch);
    }

    public function update(BatchRequest $request, string $batch, BatchService $service)
    {
        $batch = Batch::findByUuidOrFail($batch);

        $this->authorize('update', $batch);

        $service->update($request, $batch);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.batch.batch')]),
        ]);
    }

    public function destroy(string $batch, BatchService $service)
    {
        $batch = Batch::findByUuidOrFail($batch);

        $this->authorize('delete', $batch);

        $service->deletable($batch);

        $batch->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('academic.batch.batch')]),
        ]);
    }
}
