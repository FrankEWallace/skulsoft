<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\BlockRequest;
use App\Http\Resources\Site\BlockResource;
use App\Models\Site\Block;
use App\Services\Site\BlockListService;
use App\Services\Site\BlockService;
use Illuminate\Http\Request;

class BlockController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:site:manage');
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, BlockService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, BlockListService $service)
    {
        return $service->paginate($request);
    }

    public function store(BlockRequest $request, BlockService $service)
    {
        $block = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('site.block.block')]),
            'block' => BlockResource::make($block),
        ]);
    }

    public function show(Block $block, BlockService $service)
    {
        $block->load('menu');

        return BlockResource::make($block);
    }

    public function update(BlockRequest $request, Block $block, BlockService $service)
    {
        $service->update($request, $block);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('site.block.block')]),
        ]);
    }

    public function destroy(Block $block, BlockService $service)
    {
        $service->deletable($block);

        $block->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('site.block.block')]),
        ]);
    }

    public function downloadMedia(Block $block, string $uuid, BlockService $service)
    {
        return $block->downloadMedia($uuid);
    }
}
