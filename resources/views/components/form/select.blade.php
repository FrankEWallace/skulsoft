@props([
    'placeholder' => '',
    'options' => [],
    'key' => null,
    'label' => null,
    'selected' => null,
    'customOption' => false,
])

<select
    {{ $attributes->merge(['class' => 'w-full text-gray-800 dark:text-gray-400 dark:bg-transparent shadow-sm text-sm px-4 pr-8 placeholder-gray-500 dark:placeholder-gray-400 focus:ring-site-primary focus:border-site-primary border-gray-700 dark:border-gray-500 rounded-md']) }}>
    @if (!empty($placeholder))
        <option value="">{{ $placeholder }}</option>
    @endif

    @if ($customOption)
        {{ $slot }}
    @else
        @foreach ($options as $index => $option)
            <option value="{{ $key ? Arr::get($option, $key) : $index }}"
                @if ($selected === ($key ? Arr::get($option, $key) : $index)) selected="selected" @endif>
                {{ $label ? Arr::get($option, $label) : $option }}</option>
        @endforeach
    @endif
</select>
