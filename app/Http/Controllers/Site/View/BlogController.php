<?php

namespace App\Http\Controllers\Site\View;

use App\Enums\Blog\Status;
use App\Enums\Blog\Visibility;
use App\Http\Controllers\Controller;
use App\Models\Blog\Blog;
use App\Models\Site\Menu;
use App\Support\MarkdownParser;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    use MarkdownParser;

    public function __invoke(Request $request, string $slug, string $blog)
    {
        $isUuid = Str::isUuid($blog);

        $blog = Blog::query()
            ->with('category', 'tags')
            ->when($isUuid, function ($query) use ($blog) {
                $query->whereUuid($blog);
            }, function ($query) use ($blog) {
                $query->whereSlug($blog);
            })
            ->where('status', Status::PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now()->toDateTimeString())
            ->whereVisibility(Visibility::PUBLIC)
            ->firstOrFail();

        $blog->content = $this->parse($blog->content);

        $relatedCategoryBlogs = Blog::query()
            ->with('category', 'tags')
            ->where('id', '!=', $blog->id)
            ->where('category_id', $blog->category_id)
            ->where('status', Status::PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now()->toDateTimeString())
            ->orderBy('published_at', 'desc')
            ->limit(3)
            ->get();

        $relatedTagsBlog = Blog::query()
            ->with('category', 'tags')
            ->where('id', '!=', $blog->id)
            ->where('status', Status::PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now()->toDateTimeString())
            ->whereHas('tags', function ($query) use ($blog) {
                $query->whereIn('tags.id', $blog->tags->pluck('id')->all());
            })
            ->orderBy('published_at', 'desc')
            ->first();

        $menu = Menu::query()
            ->whereSlug($slug)
            ->first();

        return view(config('config.site.view').'blog', compact('blog', 'relatedCategoryBlogs', 'relatedTagsBlog', 'slug', 'menu'));
    }
}
