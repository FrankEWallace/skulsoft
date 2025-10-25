<?php

namespace App\Services\Blog;

use App\Actions\CreateTag;
use App\Concerns\HasStorage;
use App\Enums\Blog\Status;
use App\Helpers\CalHelper;
use App\Models\Blog\Blog;
use App\Models\Option;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BlogActionService
{
    use HasStorage;

    public function updateMeta(Request $request, Blog $blog)
    {
        $request->validate([
            'published_at' => 'nullable|date_format:Y-m-d H:i:s',
            'slug' => ['required', 'min:3', 'max:255', 'alpha_dash', Rule::unique('blogs')->ignore($blog->id)],
            'seo.robots' => 'boolean',
            'seo.meta_title' => 'nullable|min:3|max:255',
            'seo.meta_description' => 'nullable|min:3|max:255',
            'seo.meta_keywords' => 'nullable|min:3|max:255',
            'category' => 'nullable|uuid',
            'tags' => 'array',
            'tags.*' => 'required|string|distinct',
        ], [], [
            'published_at' => trans('blog.props.published_at'),
            'slug' => trans('blog.props.slug_placeholder'),
            'seo.robots' => trans('blog.props.seo.robots'),
            'seo.meta_title' => trans('blog.props.seo.meta_title'),
            'seo.meta_description' => trans('blog.props.seo.meta_description'),
            'seo.meta_keywords' => trans('blog.props.seo.meta_keywords'),
            'category' => trans('blog.category.category'),
            'tags' => trans('general.tag'),
        ]);

        $blogCategory = $request->category ? Option::whereType('blog_category')->whereUuid($request->category)->getOrFail(trans('blog.category.category'), 'category') : null;

        $blog->status = $request->published_at ? Status::PUBLISHED : Status::DRAFT;
        $blog->published_at = CalHelper::storeDateTime($request->published_at)?->toDateTimeString();
        $blog->category_id = $blogCategory?->id;
        $blog->slug = $request->slug;
        $blog->seo = [
            'robots' => $request->boolean('seo.robots'),
            'meta_title' => $request->input('seo.meta_title'),
            'meta_description' => $request->input('seo.meta_description'),
            'meta_keywords' => $request->input('seo.meta_keywords'),
        ];
        $blog->save();

        $tags = (new CreateTag)->execute($request->input('tags', []));

        $blog->tags()->sync($tags);

        $blog->updateMedia($request);
    }

    public function uploadAsset(Request $request, Blog $blog, string $type)
    {
        request()->validate([
            'image' => 'required|image',
        ]);

        $assets = $blog->assets;
        $asset = Arr::get($assets, $type);

        $this->deleteImageFile(
            visibility: 'public',
            path: $asset,
        );

        $image = $this->uploadImageFile(
            visibility: 'public',
            path: 'blog/assets/'.$type,
            input: 'image',
            url: false
        );

        $assets[$type] = $image;
        $blog->assets = $assets;
        $blog->save();
    }

    public function removeAsset(Request $request, Blog $blog, string $type)
    {
        $assets = $blog->assets;
        $asset = Arr::get($assets, $type);

        $this->deleteImageFile(
            visibility: 'public',
            path: $asset,
        );

        unset($assets[$type]);
        $blog->assets = $assets;
        $blog->save();
    }

    public function archive(Request $request, Blog $blog): void
    {
        if ($blog->archived_at->value) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        $blog->archived_at = now();
        $blog->save();
    }

    public function unarchive(Request $request, Blog $blog): void
    {
        if (empty($blog->archived_at->value)) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        $blog->archived_at = null;
        $blog->save();
    }

    public function pin(Request $request, Blog $blog): void
    {
        if ($blog->pinned_at->value) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        $blog->pinned_at = now();
        $blog->save();
    }

    public function unpin(Request $request, Blog $blog): void
    {
        if (empty($blog->pinned_at->value)) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        $blog->pinned_at = null;
        $blog->save();
    }
}
