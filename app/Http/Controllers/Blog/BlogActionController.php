<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use App\Models\Blog\Blog;
use App\Services\Blog\BlogActionService;
use Illuminate\Http\Request;

class BlogActionController extends Controller
{
    public function updateMeta(Request $request, BlogActionService $service, Blog $blog)
    {
        $this->authorize('update', $blog);

        $service->updateMeta($request, $blog);

        return response()->success(['message' => trans('global.updated', ['attribute' => trans('blog.blog')])]);
    }

    public function uploadAsset(Request $request, BlogActionService $service, Blog $blog, string $type)
    {
        $this->authorize('update', $blog);

        $service->uploadAsset($request, $blog, $type);

        return response()->ok();
    }

    public function removeAsset(Request $request, BlogActionService $service, Blog $blog, string $type)
    {
        $this->authorize('update', $blog);

        $service->removeAsset($request, $blog, $type);

        return response()->ok();
    }

    public function archive(Request $request, Blog $blog, BlogActionService $service)
    {
        $this->authorize('update', $blog);

        $service->archive($request, $blog);

        return response()->success([
            'message' => trans('global.archived', ['attribute' => trans('blog.blog')]),
        ]);
    }

    public function unarchive(Request $request, Blog $blog, BlogActionService $service)
    {
        $this->authorize('update', $blog);

        $service->unarchive($request, $blog);

        return response()->success([
            'message' => trans('global.unarchived', ['attribute' => trans('blog.blog')]),
        ]);
    }

    public function pin(Request $request, Blog $blog, BlogActionService $service)
    {
        $this->authorize('update', $blog);

        $service->pin($request, $blog);

        return response()->success([
            'message' => trans('global.pinned', ['attribute' => trans('blog.blog')]),
        ]);
    }

    public function unpin(Request $request, Blog $blog, BlogActionService $service)
    {
        $this->authorize('update', $blog);

        $service->unpin($request, $blog);

        return response()->success([
            'message' => trans('global.unpinned', ['attribute' => trans('blog.blog')]),
        ]);
    }
}
