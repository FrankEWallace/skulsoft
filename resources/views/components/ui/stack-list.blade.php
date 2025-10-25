@props([])

<div {{ $attributes->merge(['class' => 'overflow-hidden bg-white dark:bg-gray-800 shadow sm:rounded-md']) }}>
    <ul role="list" class="divide-y divide-gray-200 dark:divide-gray-700">
        {{ $slot }}
    </ul>
</div>
