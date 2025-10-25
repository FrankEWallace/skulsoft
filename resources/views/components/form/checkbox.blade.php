@props(['value', 'name', 'label' => '', 'inline' => false, 'reverse' => false, 'error' => false])

<fieldset class="mt-4">
    <div @class([
        'flex space-x-2 items-center' => $inline,
        'flex space-y-1' => !$inline,
    ])>
        @if (!$reverse && $label)
            <legend class="block text-sm font-medium text-gray-400">{{ $label }}</legend>
        @endif
        <div class="space-y-4 sm:flex sm:items-center sm:space-x-10 sm:space-y-0">
            <input type="checkbox" name="{{ $name }}" wire:model.lazy="{{ $name }}"
                value="{{ $value }}" class="text-site-primary h-4 w-4 border-gray-700" />
        </div>
        @if ($reverse && $label)
            <legend class="block text-sm font-medium text-gray-400">{{ $label }}</legend>
        @endif
    </div>

    @if ($error)
        <div class="mt-1 text-sm text-red-500">{{ $error }}</div>
    @endif
</fieldset>
