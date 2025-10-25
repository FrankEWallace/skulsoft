@props([
    'label' => '',
    'for' => '',
    'error' => false,
    'helpText' => false,
    'inline' => false,
    'paddingless' => true,
    'borderless' => true,
])

@if (!$inline)
    <div {{ $attributes }}>
        <label for="{{ $for }}" class="block text-sm font-medium text-gray-400">{{ $label }}</label>

        <div class="mt-1">
            {{ $slot }}

            @if ($error)
                <div class="mt-1 text-sm text-red-500">{{ $error }}</div>
            @endif

            @if ($helpText)
                <p class="mt-2 text-sm text-gray-500">{{ $helpText }}</p>
            @endif
        </div>
    </div>
@else
    <div
        class="{{ $borderless ? '' : ' sm:border-t ' }} {{ $paddingless ? '' : ' sm:py-5 ' }} sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:border-gray-200">
        @if ($label)
            <label for="{{ $for }}" class="block text-sm font-medium leading-5 text-gray-700 sm:mt-px sm:pt-2">
                {{ $label }}
            </label>
        @endif

        <div class="mt-1 sm:col-span-2 sm:mt-0">
            {{ $slot }}

            @if ($error)
                <div class="mt-1 text-sm text-red-500">{{ $error }}</div>
            @endif

            @if ($helpText)
                <p class="mt-2 text-sm text-gray-500">{{ $helpText }}</p>
            @endif
        </div>
    </div>
@endif
