@props(['navs' => []])

<x-ui.container class="mt-4 sm:mt-12">
    <nav class="flex sm:px-4" aria-label="Breadcrumb">
        <ol role="list" class="flex items-center space-x-4">
            <li>
                <div>
                    <a href="{{ route('site.home') }}"
                        class="text-gray-400 hover:text-gray-500 dark:text-gray-300 dark:hover:text-gray-400">
                        <i class="fas fa-home"></i> <span
                            class="ml-2 text-sm font-medium">{{ trans('dashboard.home') }}</span>
                        <span class="sr-only">{{ trans('dashboard.home') }}</span>
                    </a>
                </div>
            </li>

            @foreach ($navs as $nav)
                <li>
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 dark:text-gray-300"></i>
                        @if (Arr::get($nav, 'url'))
                            <a href="{{ Arr::get($nav, 'url') }}"
                                class="ml-4 text-sm font-medium text-gray-400 hover:text-gray-500 dark:text-gray-300 dark:hover:text-gray-400">{{ Arr::get($nav, 'name') }}</a>
                        @else
                            <span
                                class="ml-4 text-sm font-medium text-gray-400 dark:text-gray-300">{{ Arr::get($nav, 'name') }}</span>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    </nav>
</x-ui.container>
