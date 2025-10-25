<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\LedgerRequest;
use App\Http\Resources\Finance\LedgerResource;
use App\Models\Finance\Ledger;
use App\Services\Finance\LedgerListService;
use App\Services\Finance\LedgerService;
use Illuminate\Http\Request;

class LedgerController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(LedgerService $service)
    {
        return response()->ok($service->preRequisite());
    }

    public function index(Request $request, LedgerListService $service)
    {
        $this->authorize('viewAny', Ledger::class);

        return $service->paginate($request);
    }

    public function store(LedgerRequest $request, LedgerService $service)
    {
        $this->authorize('create', Ledger::class);

        $ledger = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('finance.ledger.ledger')]),
            'ledger' => LedgerResource::make($ledger),
        ]);
    }

    public function show(Ledger $ledger, LedgerService $service)
    {
        $this->authorize('view', $ledger);

        return LedgerResource::make($ledger);
    }

    public function update(LedgerRequest $request, Ledger $ledger, LedgerService $service)
    {
        $this->authorize('update', $ledger);

        $service->update($ledger, $request);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('finance.ledger.ledger')]),
        ]);
    }

    public function destroy(Ledger $ledger, LedgerService $service)
    {
        $this->authorize('delete', $ledger);

        $service->deletable($ledger);

        $ledger->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('finance.ledger.ledger')]),
        ]);
    }
}
