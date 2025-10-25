<?php

namespace App\Http\Controllers\Hostel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hostel\BlockRequest;
use App\Http\Resources\Hostel\BlockResource;
use App\Models\Hostel\Block;
use App\Services\Hostel\BlockListService;
use App\Services\Hostel\BlockService;
use Illuminate\Http\Request;

class BlockController extends Controller
{
    public function __construct()
    {
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, BlockService $service)
    {
        return response()->ok($service->preRequisite($request));
    }

    public function index(Request $request, BlockListService $service)
    {
        return $service->paginate($request);
    }

    public function store(BlockRequest $request, BlockService $service)
    {
        $block = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('hostel.block.block')]),
            'block' => BlockResource::make($block),
        ]);
    }

    public function show(Request $request, string $block, BlockService $service)
    {
        $block = Block::findByUuidOrFail($block);

        $request->merge(['details' => true]);

        $block->load(['incharges.employee' => fn ($q) => $q->detail()]);

        return BlockResource::make($block);
    }

    public function update(BlockRequest $request, string $block, BlockService $service)
    {
        $block = Block::findByUuidOrFail($block);

        $service->update($request, $block);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('hostel.block.block')]),
        ]);
    }

    public function destroy(string $block, BlockService $service)
    {
        $block = Block::findByUuidOrFail($block);

        $service->deletable($block);

        $block->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('hostel.block.block')]),
        ]);
    }
}
