@props([
    'color' => 'success',
    'closable' => false,
])

<div class="py-2" x-data="{ open: true }" x-show="open">
    <div @class([
        'flex justify-between items-center px-4 py-2 rounded',
        'bg-success text-green-50' => $color == 'success',
        'bg-danger text-red-50' => $color == 'danger',
        'bg-warning text-yellow-50' => $color == 'warning',
        'bg-info text-blue-50' => $color == 'info',
        'bg-primary text-white' => $color == 'primary',
        'bg-secondary text-gray-800' => $color == 'secondary',
    ])>
        <div @class([
            'pr-10' => $closable,
        ])>{{ $slot }}</div>
        @if ($closable)
            <i class="fas fa-times cursor-pointer" @click="open = false"></i>
        @endif
    </div>
</div>
