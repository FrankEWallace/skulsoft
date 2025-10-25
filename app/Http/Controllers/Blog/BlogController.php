<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Blog\BlogRequest;
use App\Http\Resources\Blog\BlogResource;
use App\Http\Resources\Blog\BlogSummaryResource;
use App\Models\Blog\Blog;
use App\Services\Blog\BlogListService;
use App\Services\Blog\BlogService;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function preRequisite(BlogService $service)
    {
        return response()->ok($service->preRequisite());
    }

    public function index(Request $request, BlogListService $service)
    {
        $this->authorize('viewAny', Blog::class);

        return $service->paginate($request);
    }

    public function store(BlogRequest $request, BlogService $service)
    {
        $this->authorize('create', Blog::class);

        $blog = $service->create($request);

        return response()->success([
            'message' => trans('global.created', ['attribute' => trans('blog.blog')]),
            'blog' => BlogSummaryResource::make($blog),
        ]);
    }

    public function show(string $blog, BlogService $service): BlogResource
    {
        $blog = $service->findByUuidOrFail($blog);

        $blog->load('category', 'media', 'tags');

        $this->authorize('view', $blog);

        return BlogResource::make($blog);
    }

    public function update(BlogRequest $request, string $blog, BlogService $service)
    {
        $blog = $service->findByUuidOrFail($blog);

        $this->authorize('update', $blog);

        $service->update($request, $blog);

        return response()->success([
            'message' => trans('global.updated', ['attribute' => trans('blog.blog')]),
        ]);
    }

    public function destroy(string $blog, BlogService $service)
    {
        $blog = $service->findByUuidOrFail($blog);

        $this->authorize('delete', $blog);

        $service->deletable($blog);

        $blog->delete();

        return response()->success([
            'message' => trans('global.deleted', ['attribute' => trans('blog.blog')]),
        ]);
    }

    public function downloadMedia(string $blog, string $uuid, BlogService $service)
    {
        $blog = $service->findByUuidOrFail($blog);

        $this->authorize('view', $blog);

        return $blog->downloadMedia($uuid);
    }

    public function archiveMultiple(Request $request, BlogService $service)
    {
        $this->authorize('update', Blog::class);

        $count = $service->archiveMultiple($request);

        return response()->success([
            'message' => trans('global.multiple_archived', ['count' => $count, 'attribute' => trans('blog.blog')]),
        ]);
    }

    public function unarchiveMultiple(Request $request, BlogService $service)
    {
        $this->authorize('update', Blog::class);

        $count = $service->unarchiveMultiple($request);

        return response()->success([
            'message' => trans('global.multiple_unarchived', ['count' => $count, 'attribute' => trans('blog.blog')]),
        ]);
    }

    public function destroyMultiple(Request $request, BlogService $service)
    {
        $this->authorize('delete', Blog::class);

        $count = $service->deleteMultiple($request);

        return response()->success([
            'message' => trans('global.multiple_deleted', ['count' => $count, 'attribute' => trans('blog.blog')]),
        ]);
    }
}
