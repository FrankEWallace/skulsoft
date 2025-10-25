<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\InchargeRequest;
use App\Http\Resources\Inventory\InchargeResource;
use App\Models\Incharge;
use App\Services\Inventory\InchargeListService;
use App\Services\Inventory\InchargeService;
use Illuminate\Http\Request;

class InchargeController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, InchargeService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, InchargeListService $service)
    {
        return $service->paginate($request);
    }

    public function store(InchargeRequest $request, InchargeService $service)
    {
        $incharge = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('inventory.incharge.incharge')]),
            'incharge' => InchargeResource::make($incharge),
        ]);
    }

    public function show(string $incharge, InchargeService $service)
    {
        $incharge = Incharge::findByUuidOrFail($incharge);

        $incharge->load(['model', 'employee' => fn ($q) => $q->summary()]);

        return InchargeResource::make($incharge);
    }

    public function update(InchargeRequest $request, string $incharge, InchargeService $service)
    {
        $incharge = Incharge::findByUuidOrFail($incharge);

        $service->update($request, $incharge, 'division');

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('inventory.incharge.incharge')]),
        ]);
    }

    public function destroy(string $incharge, InchargeService $service)
    {
        $incharge = Incharge::findByUuidOrFail($incharge);

        $service->deletable($incharge);

        $incharge->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('inventory.incharge.incharge')]),
        ]);
    }
}
