<?php

namespace App\Http\Controllers\Asset\Building;

use App\Http\Controllers\Controller;
use App\Http\Requests\Asset\Building\BlockRequest;
use App\Http\Resources\Asset\Building\BlockResource;
use App\Models\Asset\Building\Block;
use App\Services\Asset\Building\BlockListService;
use App\Services\Asset\Building\BlockService;
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
            'message' => trans('global.created', ['attribute' => trans('asset.building.block.block')]),
            'block' => BlockResource::make($block),
        ]);
    }

    public function show(string $block, BlockService $service)
    {
        $block = Block::findByUuidOrFail($block);

        return BlockResource::make($block);
    }

    public function update(BlockRequest $request, string $block, BlockService $service)
    {
        $block = Block::findByUuidOrFail($block);

        $service->update($request, $block);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('asset.building.block.block')]),
        ]);
    }

    public function destroy(string $block, BlockService $service)
    {
        $block = Block::findByUuidOrFail($block);

        $service->deletable($block);

        $block->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('asset.building.block.block')]),
        ]);
    }
}
