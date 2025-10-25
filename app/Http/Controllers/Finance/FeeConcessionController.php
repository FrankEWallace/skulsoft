<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\FeeConcessionRequest;
use App\Http\Resources\Finance\FeeConcessionResource;
use App\Models\Finance\FeeConcession;
use App\Services\Finance\FeeConcessionListService;
use App\Services\Finance\FeeConcessionService;
use Illuminate\Http\Request;

class FeeConcessionController extends Controller
{
    public function preRequisite(FeeConcessionService $service)
    {
        return response()->ok($service->preRequisite());
    }

    public function index(Request $request, FeeConcessionListService $service)
    {
        $this->authorize('viewAny', FeeConcession::class);

        return $service->paginate($request);
    }

    public function store(FeeConcessionRequest $request, FeeConcessionService $service)
    {
        $this->authorize('create', FeeConcession::class);

        $feeConcession = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('finance.fee_concession.fee_concession')]),
            'fee_concession' => FeeConcessionResource::make($feeConcession),
        ]);
    }

    public function show(string $feeConcession, FeeConcessionService $service): FeeConcessionResource
    {
        $feeConcession = $service->findByUuidOrFail($feeConcession);

        $this->authorize('view', $feeConcession);

        $feeConcession->load('records.head');

        return FeeConcessionResource::make($feeConcession);
    }

    public function update(FeeConcessionRequest $request, string $feeConcession, FeeConcessionService $service)
    {
        $feeConcession = $service->findByUuidOrFail($feeConcession);

        $this->authorize('update', $feeConcession);

        $service->update($request, $feeConcession);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('finance.fee_concession.fee_concession')]),
        ]);
    }

    public function destroy(string $feeConcession, FeeConcessionService $service)
    {
        $feeConcession = $service->findByUuidOrFail($feeConcession);

        $this->authorize('delete', $feeConcession);

        $service->deletable($feeConcession);

        $feeConcession->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('finance.fee_concession.fee_concession')]),
        ]);
    }
}
