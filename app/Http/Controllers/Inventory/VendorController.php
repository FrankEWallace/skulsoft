<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\LedgerRequest;
use App\Http\Resources\Finance\LedgerResource;
use App\Models\Finance\Ledger;
use App\Models\Inventory\Vendor;
use App\Services\Finance\LedgerService;
use App\Services\Inventory\VendorListService;
use App\Services\Inventory\VendorService;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, VendorService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, VendorListService $service)
    {
        $this->authorize('viewAny', Vendor::class);

        return $service->paginate($request);
    }

    public function store(LedgerRequest $request, LedgerService $service)
    {
        $this->authorize('create', Vendor::class);

        $vendor = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('inventory.vendor.vendor')]),
            'vendor' => LedgerResource::make($vendor),
        ]);
    }

    public function show(Vendor $vendor, LedgerService $service)
    {
        $this->authorize('view', $vendor);

        $ledger = Ledger::find($vendor->id);

        return LedgerResource::make($ledger);
    }

    public function update(LedgerRequest $request, Vendor $vendor, LedgerService $service)
    {
        $this->authorize('update', $vendor);

        $ledger = Ledger::find($vendor->id);

        $service->update($ledger, $request);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('inventory.vendor.vendor')]),
        ]);
    }

    public function destroy(Request $request, Vendor $vendor, LedgerService $service)
    {
        $this->authorize('delete', $vendor);

        $ledger = Ledger::find($vendor->id);

        $service->deletable($ledger);

        $vendor->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('inventory.vendor.vendor')]),
        ]);
    }
}
