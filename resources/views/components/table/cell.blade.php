@props([
    'first' => false,
])

@if ($first)
    <td class="relative py-4 pl-6 pr-3 text-sm">
        {{ $slot }}
    </td>
@else
    <td class="hidden px-3 py-3.5 text-sm text-gray-500 lg:table-cell">
        {{ $slot }}
    </td>
@endif
