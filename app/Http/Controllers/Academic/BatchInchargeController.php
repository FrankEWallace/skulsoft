<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\BatchInchargeRequest;
use App\Http\Resources\Academic\BatchInchargeResource;
use App\Models\Incharge;
use App\Services\Academic\BatchInchargeListService;
use App\Services\Academic\BatchInchargeService;
use Illuminate\Http\Request;

class BatchInchargeController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, BatchInchargeService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, BatchInchargeListService $service)
    {
        $this->authorize('viewAny', Incharge::class, 'batch');

        return $service->paginate($request);
    }

    public function store(BatchInchargeRequest $request, BatchInchargeService $service)
    {
        $this->authorize('create', Incharge::class, 'batch');

        $batchIncharge = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('academic.batch_incharge.batch_incharge')]),
            'batch_incharge' => BatchInchargeResource::make($batchIncharge),
        ]);
    }

    public function show(string $batchIncharge, BatchInchargeService $service)
    {
        $batchIncharge = Incharge::findByUuidOrFail($batchIncharge);

        $this->authorize('view', $batchIncharge, 'batch');

        $batchIncharge->load('model.course');

        return BatchInchargeResource::make($batchIncharge);
    }

    public function update(BatchInchargeRequest $request, string $batchIncharge, BatchInchargeService $service)
    {
        $batchIncharge = Incharge::findByUuidOrFail($batchIncharge);

        $this->authorize('update', $batchIncharge);

        $service->update($request, $batchIncharge, 'batch');

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('academic.batch_incharge.batch_incharge')]),
        ]);
    }

    public function destroy(string $batchIncharge, BatchInchargeService $service)
    {
        $batchIncharge = Incharge::findByUuidOrFail($batchIncharge);

        $this->authorize('delete', $batchIncharge, 'batch');

        $service->deletable($batchIncharge);

        $batchIncharge->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('academic.batch_incharge.batch_incharge')]),
        ]);
    }
}
