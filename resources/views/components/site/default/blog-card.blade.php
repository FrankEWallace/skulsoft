@props(['blog', 'menu'])

<div class="flex flex-col overflow-hidden rounded-lg border border-gray-200 shadow-lg dark:border-gray-700">
    <div class="flex-shrink-0">
        <div class="relative">
            <a href="{{ '/pages/' . $menu->slug . '/' . $blog->slug }}">
                <img class="lozad h-48 w-full object-cover" data-src="{{ $blog->cover_image }}"
                    alt="{{ $blog->title }}" />
            </a>
            <div class="absolute top-0 right-0 mr-2 mt-2">
                <img class="h-8 w-8" src="{{ config('config.assets.icon') }}" alt="icon" />
            </div>
            <div class="absolute bottom-0 w-full bg-black bg-opacity-70">
                <div class="truncate px-4 text-lg font-semibold text-gray-200">
                    {{ $blog->title }}
                </div>
            </div>
        </div>
    </div>
    <div class="flex flex-1 flex-col justify-between bg-white p-6 dark:bg-gray-800">
        <div class="flex-1">
            <div class="flex justify-between">
                <p class="text-primary text-sm font-medium dark:text-gray-300">
                    <i class="fas fa-calendar"></i> {{ $blog->published_at->formatted }}
                </p>
                @if ($blog->category)
                    <p class="text-primary text-sm font-medium">
                        <a
                            href="{{ route('site.page.blog-list-category', ['slug' => $menu->slug, 'category' => $blog->category->slug]) }}">
                            <x-ui.badge color="custom" :color-value="$blog->category->color">{{ $blog->category->name }}</x-ui.badge>
                        </a>
                    </p>
                @endif
            </div>
            <a href="{{ '/pages/' . $menu->slug . '/' . $blog->slug }}" class="mt-2 block">
                <p class="text-xl font-semibold text-gray-900 dark:text-gray-300">{{ $blog->title }}</p>
                <p class="mt-3 text-base text-gray-500 dark:text-gray-400">{{ Str::summary($blog->sub_title) }}</p>
            </a>
        </div>

        @if ($blog->tags->count())
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach ($blog->tags as $tag)
                    <a class="truncate rounded-lg bg-gray-200 px-2 py-1 text-sm text-gray-800 dark:bg-gray-500 dark:text-gray-300"
                        href="{{ route('site.page.blog-list-tag', ['slug' => $menu->slug, 'tag' => strtolower($tag->name)]) }}">#{{ $tag->name }}</a>
                @endforeach
            </div>
        @endif
    </div>
</div>
