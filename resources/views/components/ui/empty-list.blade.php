@props(['title', 'description' => ''])

<div
    {{ $attributes->merge(['class' => 'w-full p-4 text-center bg-white border border-gray-200 rounded-lg shadow sm:p-8 dark:bg-gray-800 dark:border-gray-700']) }}>
    <x-ui.heading heading="h5" class="mb-2 text-3xl font-bold text-gray-900 dark:text-white">
        {{ $title }}
    </x-ui.heading>
    @if ($description)
        <p class="mb-5 text-base text-gray-500 dark:text-gray-400 sm:text-lg">{{ $description }}</p>
    @endif
    <div class="items-center justify-center space-y-4 sm:flex sm:space-y-0 sm:space-x-4">
        {{ $slot }}
    </div>
</div>
