@props(['title' => ''])

<fieldset
    {{ $attributes->merge(['class' => 'border rounded-md border-solid border-gray-300 dark:border-gray-700 p-3']) }}>
    <legend
        class="rounded border border-gray-300 p-2 text-sm font-semibold text-gray-800 dark:border-gray-700 dark:text-gray-300">
        {{ $title }}
    </legend>
    {{ $slot }}
</fieldset>
