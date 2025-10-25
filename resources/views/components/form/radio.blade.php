@props([
    'options' => [],
    'name',
    'label' => '',
    'error' => false,
])

<fieldset class="mt-4">
    @if ($label)
        <legend class="mb-1 block text-sm font-medium text-gray-400">{{ $label }}</legend>
    @endif
    <div class="space-y-4 sm:flex sm:items-center sm:space-y-0 sm:space-x-10">
        @foreach ($options as $option)
            <div class="flex items-center">
                <input name="{{ $name }}" wire:model.lazy="{{ $name }}" type="radio"
                    value="{{ Arr::get($option, 'value') }}" class="border-primary text-primary h-4 w-4" />
                <label for="{{ Arr::get($option, 'value') }}"
                    class="ml-3 block text-sm font-medium text-gray-400">{{ Arr::get($option, 'label') }}</label>
            </div>
        @endforeach
    </div>

    @if ($error)
        <div class="mt-1 text-sm text-red-500">{{ $error }}</div>
    @endif
</fieldset>
