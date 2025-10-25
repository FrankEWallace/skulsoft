<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\PageRequest;
use App\Http\Resources\Site\PageResource;
use App\Models\Site\Page;
use App\Services\Site\PageListService;
use App\Services\Site\PageService;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:site:manage');
        $this->middleware('test.mode.restriction')->only(['destroy']);
    }

    public function preRequisite(Request $request, PageService $service)
    {
        return $service->preRequisite($request);
    }

    public function index(Request $request, PageListService $service)
    {
        return $service->paginate($request);
    }

    public function store(PageRequest $request, PageService $service)
    {
        $page = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('site.page.page')]),
            'page' => PageResource::make($page),
        ]);
    }

    public function show(Request $request, Page $page, PageService $service)
    {
        $request->merge(['with_details' => true]);

        $page->load('media');

        return PageResource::make($page);
    }

    public function update(PageRequest $request, Page $page, PageService $service)
    {
        $service->update($request, $page);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('site.page.page')]),
        ]);
    }

    public function destroy(Page $page, PageService $service)
    {
        $service->deletable($page);

        $page->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('site.page.page')]),
        ]);
    }

    public function downloadMedia(Page $page, string $uuid, PageService $service)
    {
        return $page->downloadMedia($uuid);
    }
}
