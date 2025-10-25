<?php

namespace App\Services\Blog;

use App\Enums\Blog\Status;
use App\Enums\Blog\Visibility;
use App\Models\Blog\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BlogService
{
    public function preRequisite(): array
    {
        $url = config('app.url').'/blogs/';

        return compact('url');
    }

    public function findByUuidOrFail(string $uuid): Blog
    {
        return Blog::query()
            ->findIfExists($uuid);
    }

    public function create(Request $request): Blog
    {
        \DB::beginTransaction();

        $blog = Blog::forceCreate($this->formatParams($request));

        \DB::commit();

        return $blog;
    }

    private function formatParams(Request $request, ?Blog $blog = null): array
    {
        $formatted = [
            'title' => $request->title,
            'sub_title' => $request->sub_title,
            'content' => $request->content,
        ];

        if (! $blog) {
            $formatted['slug'] = Str::slug($request->title).'-'.uniqid();
            $formatted['status'] = Status::DRAFT;
            $formatted['visibility'] = Visibility::PUBLIC;
            $formatted['seo']['robots'] = true;
        }

        return $formatted;
    }

    public function update(Request $request, Blog $blog): void
    {
        \DB::beginTransaction();

        $blog->forceFill($this->formatParams($request, $blog))->save();

        \DB::commit();
    }

    public function deletable(Blog $blog, $validate = false): ?bool
    {
        return true;
    }

    private function findMultiple(Request $request): array
    {
        if ($request->boolean('global')) {
            $listService = new BlogListService;
            $uuids = $listService->getIds($request);
        } else {
            $uuids = is_array($request->uuids) ? $request->uuids : [];
        }

        if (! count($uuids)) {
            throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('general.data')])]);
        }

        return $uuids;
    }

    public function archiveMultiple(Request $request): int
    {
        $uuids = $this->findMultiple($request);

        $blogs = Blog::whereIn('uuid', $uuids)->get();

        $archivable = [];
        foreach ($blogs as $blog) {
            if (empty($blog->archived_at->value)) {
                $archivable[] = $blog->uuid;
            }
        }

        if (! count($archivable)) {
            throw ValidationException::withMessages(['message' => trans('global.could_not_update_any', ['attribute' => trans('blog.blog')])]);
        }

        Blog::whereIn('uuid', $archivable)->update(['archived_at' => now()]);

        return count($archivable);
    }

    public function unarchiveMultiple(Request $request): int
    {
        $request->merge(['is_archived' => true]);
        $uuids = $this->findMultiple($request);

        $blogs = Blog::whereIn('uuid', $uuids)->get();

        $unarchivable = [];
        foreach ($blogs as $blog) {
            if ($blog->archived_at->value) {
                $unarchivable[] = $blog->uuid;
            }
        }

        if (! count($unarchivable)) {
            throw ValidationException::withMessages(['message' => trans('global.could_not_update_any', ['attribute' => trans('blog.blog')])]);
        }

        Blog::whereIn('uuid', $unarchivable)->update(['archived_at' => null]);

        return count($unarchivable);
    }

    public function deleteMultiple(Request $request): int
    {
        $uuids = $this->findMultiple($request);

        $blogs = Blog::whereIn('uuid', $uuids)->get();

        $deletable = [];
        foreach ($blogs as $blog) {
            if ($this->deletable($blog, true)) {
                $deletable[] = $blog->uuid;
            }
        }

        if (! count($deletable)) {
            throw ValidationException::withMessages(['message' => trans('global.could_not_delete_any', ['attribute' => trans('blog.blog')])]);
        }

        Blog::whereIn('uuid', $deletable)->delete();

        return count($deletable);
    }
}
