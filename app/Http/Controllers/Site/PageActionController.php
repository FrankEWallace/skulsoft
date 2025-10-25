<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Site\Page;
use App\Services\Site\PageActionService;
use Illuminate\Http\Request;

class PageActionController extends Controller
{
    public function updateMeta(Request $request, PageActionService $service, Page $page)
    {
        $service->updateMeta($request, $page);

        return response()->success(['message' => trans('global.updated', ['attribute' => trans('site.page.page')])]);
    }

    public function updateBlocks(Request $request, PageActionService $service, Page $page)
    {
        $service->updateBlocks($request, $page);

        return response()->success(['message' => trans('global.updated', ['attribute' => trans('site.page.page')])]);
    }

    public function updateSlider(Request $request, PageActionService $service, Page $page)
    {
        $service->updateSlider($request, $page);

        return response()->success(['message' => trans('global.updated', ['attribute' => trans('site.page.page')])]);
    }

    public function updateCTA(Request $request, PageActionService $service, Page $page)
    {
        $service->updateCTA($request, $page);

        return response()->success(['message' => trans('global.updated', ['attribute' => trans('site.page.page')])]);
    }

    public function uploadAsset(Request $request, PageActionService $service, Page $page, string $type)
    {
        $service->uploadAsset($request, $page, $type);

        return response()->ok();
    }

    public function removeAsset(Request $request, PageActionService $service, Page $page, string $type)
    {
        $service->removeAsset($request, $page, $type);

        return response()->ok();
    }
}
