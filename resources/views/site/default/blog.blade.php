<x-site.default.layout :robots="$blog->getSeo('robots')" :metaTitle="$blog->getSeo('meta_title') ?? $blog->title" :metaDescription="$blog->getSeo('meta_description')" :metaKeywords="$blog->getSeo('meta_keywords')" :publishedAt="$blog->published_at->toIso8601String()"
    :modifiedAt="$blog->updated_at->toIso8601String()" :imageSrc="$blog->og_image ?? $blog->cover_image">

    <div class="relative">
        <img class="lozad h-48 w-full object-cover lg:h-96" data-src="{{ $blog->cover_image }}"
            alt="{{ $blog->title }}" />
        <div class="absolute right-0 top-0 mr-2 mt-2">
            <img class="h-12 w-12" src="{{ config('config.assets.icon') }}" alt="icon" />
        </div>
        <div class="absolute bottom-0 w-full bg-black bg-opacity-70">
            <x-ui.container class="py-2">
                <div class="truncate text-3xl font-semibold text-gray-200">
                    {{ $blog->title }}
                </div>
            </x-ui.container>
        </div>
    </div>

    <section>

        <x-ui.breadcrumb :navs="[
            ['name' => trans('blog.blog'), 'url' => route('site.page', ['slug' => request()->route('slug')])],
            ['name' => $blog->title],
        ]"></x-ui.breadcrumb>

        <x-ui.container class="mt-6">
            <div class="grid grid-cols-1 gap-6">
                <div class="space-y-6 text-gray-800 dark:text-gray-400 col-span-1">
                    <x-ui.heading heading="h1">
                        {{ $blog->title }}
                    </x-ui.heading>

                    @if ($blog->sub_title)
                        <x-ui.heading heading="h6" class="texl-xl text-justify">
                            {{ $blog->sub_title }}
                        </x-ui.heading>
                    @endif

                    <div class="flex justify-between">
                        @if ($blog->category)
                            <p class="text-sm font-medium">
                                <a href="{{ route('site.page.blog-list-category', ['slug' => request()->route('slug'), 'category' => $blog->category->slug]) }}"
                                    class="hover:underline">
                                    <x-ui.badge color="custom" :color-value="$blog->category->color">{{ $blog->category->name }}
                                    </x-ui.badge>
                                </a>
                            </p>
                        @endif
                        <p class="text-sm font-medium">
                            <i class="fas fa-calendar"></i> {{ $blog->published_at->formatted }}
                        </p>
                    </div>

                    @if ($blog->tags->count())
                        <div class="flex flex-wrap gap-2">
                            @foreach ($blog->tags as $tag)
                                <a class="rounded-lg bg-gray-200 px-2 py-1 text-sm text-gray-800"
                                    href="{{ route('site.page.blog-list-tag', ['slug' => request()->route('slug'), 'tag' => strtolower($tag->name)]) }}">#{{ $tag->name }}</a>
                            @endforeach
                        </div>
                    @endif
                    <div class="md-content">
                        {!! $blog->content !!}
                    </div>
                </div>
            </div>
        </x-ui.container>
    </section>

    @if ($relatedTagsBlog)
        <section class="mb-10">
            <x-ui.container class="space-y-6">

                <x-ui.heading heading="h4">
                    {{ trans('blog.related_blog') }}
                </x-ui.heading>
                <div class="grid max-w-lg gap-5 lg:max-w-none lg:grid-cols-1">
                    <x-site.default.blog-card :blog="$relatedTagsBlog" :menu="$menu" />
                </div>
            </x-ui.container>
        </section>
    @endif

    @if ($relatedCategoryBlogs->count())
        <section class="mb-10">
            <x-ui.container class="space-y-6">
                <x-ui.heading heading="h3">
                    {{ trans('blog.related_blog') }}
                </x-ui.heading>
                <div class="grid max-w-lg gap-5 lg:max-w-none lg:grid-cols-3">
                    @foreach ($relatedCategoryBlogs as $blog)
                        <x-site.default.blog-card :blog="$blog" :menu="$menu" />
                    @endforeach
                </div>
            </x-ui.container>
        </section>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.hljs.highlightAll();
        });
    </script>
</x-site.default.layout>
