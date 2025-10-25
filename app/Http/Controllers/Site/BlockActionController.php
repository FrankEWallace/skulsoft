<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Site\Block;
use App\Services\Site\BlockActionService;
use Illuminate\Http\Request;

class BlockActionController extends Controller
{
    public function reorder(Request $request, BlockActionService $service)
    {
        $block = $service->reorder($request);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('site.block.block')]),
        ]);
    }

    public function uploadAsset(Request $request, BlockActionService $service, Block $block, string $type)
    {
        $service->uploadAsset($request, $block, $type);

        return response()->ok();
    }

    public function removeAsset(Request $request, BlockActionService $service, Block $block, string $type)
    {
        $service->removeAsset($request, $block, $type);

        return response()->ok();
    }

    public function uploadSliderImage(Request $request, Block $block, BlockActionService $service)
    {
        $sliderImage = $service->uploadSliderImage($request, $block);

        return response()->success([
            'image' => $sliderImage,
        ]);
    }

    public function deleteSliderImage(Request $request, Block $block, string $image, BlockActionService $service)
    {
        $service->deleteSliderImage($request, $block, $image);

        return response()->success([]);
    }
}
