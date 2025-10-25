<x-site.default.layout :meta-title="$metaTitle" :meta-description="$metaDescription" :meta-keywords="$metaKeywords">

    <section>
        @if ($page->has_slider)
            <x-site.default.carousel :slider-images="$sliderImages" />
        @elseif (Arr::get($page->assets, 'cover'))
            <img src="{{ $page->cover_image }}" alt="{{ $page->title }}" class="w-full h-auto">
        @endif
    </section>

    @if (route('site.home') != request()->url())
        <x-ui.breadcrumb :navs="$navs" />
    @endif

    <section class="mt-10">
        <div class="container">
            <h1 class="text-2xl text-gray-800 font-bold">{{ $page->title }}</h1>
            @if ($page->sub_title)
                <h2 class="mt-2 text-xl text-gray-700">{{ $page->sub_title }}</h2>
            @endif
        </div>
    </section>

    @foreach ($parts as $part)
        <section class="{{ !$loop->first ? 'my-10' : 'my-4' }}">
            <div class="container">
                <div class="text-gray-700">
                    @if ($part['type'] == 'html')
                        <div class="md-content">
                            {!! $part['content'] !!}
                        </div>
                    @endif

                    @if ($part['type'] == 'array')
                        <div class="grid grid-cols-{{ count($part['content']) }} gap-4">
                            @foreach ($part['content'] as $blockName)
                                @if ($blockName == 'CONTACT')
                                    <x-site.contact />
                                @elseif ($blockName == 'BLOG_LIST')
                                    <x-site.blog-list :menu="$menu" />
                                @elseif ($blockName == 'EVENT_LIST')
                                    <x-site.event-list />
                                @elseif ($blockName == 'ANNOUNCEMENT_LIST')
                                    <x-site.announcement-list />
                                @else
                                    @php
                                        $block = $blocks->firstWhere('name', $blockName);
                                    @endphp

                                    @if ($block)
                                        <div
                                            class="col-span-{{ count($part['content']) }} sm:col-span-1 flex md-content">
                                            <div
                                                class="flex flex-col w-full {{ $block->has_cover && $block->cover_image ? 'border-gray-100 border-2 rounded-md shadow-md' : '' }}">
                                                @if ($block->has_cover && $block->cover_image)
                                                    <img src="{{ $block->cover_image }}" alt="{{ $block->title }}"
                                                        class="w-full h-auto rounded-t-md">
                                                @endif
                                                <div
                                                    class="text-gray-700 flex-1 flex flex-col {{ $block->has_cover && $block->cover_image ? 'px-4 py-2' : '' }}">
                                                    <h2 class="text-xl font-bold">{{ $block->title }}</h2>
                                                    @if ($block->sub_title)
                                                        <p class="text-sm text-gray-700">{{ $block->sub_title }}</p>
                                                    @endif
                                                    <p class="mt-2 text-gray-700 flex-1">
                                                        {{ Str::limit($block->content, 1000) }}</p>

                                                    @if ($block->url)
                                                        <div class="mt-2">
                                                            <a href="{{ $block->url }}"
                                                                target="{{ $block->target_url }}"
                                                                class="text-xs mt-auto bg-site-primary rounded-md px-4 py-2 button">Read
                                                                More</a>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endforeach

    <section class="my-10">
        <div class="container">
            @if ($page->media->isNotEmpty())
                <h2 class="text-xl font-bold text-gray-700 mt-8 mb-4">Attachments</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    @foreach ($page->media as $media)
                        <a href="/app/site/pages/{{ $page->uuid }}/media/{{ $media->uuid }}">
                            <div
                                class="bg-white rounded-lg shadow-md border border-gray-200 p-4 flex items-center space-x-3 hover:shadow-lg transition-shadow duration-200">
                                <i class="fas {{ $media->getIcon() }} fa-2xl text-gray-600"></i>
                                <div class="overflow-hidden">
                                    <div class="font-medium text-gray-800 truncate">{{ $media->file_name }}</div>
                                    <div class="text-sm text-gray-500">{{ $media->size }}</div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    @include('site.default.cta', ['page' => $page])

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.hljs.highlightAll();
        });
    </script>

</x-site.default.layout>
