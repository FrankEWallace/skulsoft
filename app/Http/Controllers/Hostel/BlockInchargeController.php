<?php

namespace App\Http\Controllers\Hostel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hostel\BlockInchargeRequest;
use App\Http\Resources\Hostel\BlockInchargeResource;
use App\Models\Incharge;
use App\Services\Hostel\BlockInchargeListService;
use App\Services\Hostel\BlockInchargeService;
use Illuminate\Http\Request;

class BlockInchargeController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, BlockInchargeService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, BlockInchargeListService $service)
    {
        $this->authorize('viewAny', Incharge::class, 'hostel');

        return $service->paginate($request);
    }

    public function store(BlockInchargeRequest $request, BlockInchargeService $service)
    {
        $this->authorize('create', Incharge::class, 'hostel');

        $blockIncharge = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('hostel.block_incharge.block_incharge')]),
            'block_incharge' => BlockInchargeResource::make($blockIncharge),
        ]);
    }

    public function show(string $blockIncharge, BlockInchargeService $service)
    {
        $blockIncharge = Incharge::findByUuidOrFail($blockIncharge);

        $this->authorize('view', $blockIncharge, 'hostel');

        $blockIncharge->load('model');

        return BlockInchargeResource::make($blockIncharge);
    }

    public function update(BlockInchargeRequest $request, string $blockIncharge, BlockInchargeService $service)
    {
        $blockIncharge = Incharge::findByUuidOrFail($blockIncharge);

        $this->authorize('update', $blockIncharge);

        $service->update($request, $blockIncharge, 'hostel');

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('hostel.block_incharge.block_incharge')]),
        ]);
    }

    public function destroy(string $blockIncharge, BlockInchargeService $service)
    {
        $blockIncharge = Incharge::findByUuidOrFail($blockIncharge);

        $this->authorize('delete', $blockIncharge, 'hostel');

        $service->deletable($blockIncharge);

        $blockIncharge->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('hostel.block_incharge.block_incharge')]),
        ]);
    }
}
