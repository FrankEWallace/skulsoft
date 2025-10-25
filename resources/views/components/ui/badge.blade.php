@props([
    'size' => 'sm',
    'color' => 'primary',
    'colorValue' => '',
])

<span @class([
    'inline-flex items-center',
    'px-2 py-1 text-xs font-medium rounded-full text-white' => $size === 'xs',
    'px-2 py-1 text-sm font-medium rounded-full text-white' => $size === 'sm',
    'px-4 py-1 text-base font-medium rounded-full text-white' =>
        $size === 'base',
    'px-4 py-1 text-lg font-medium rounded-full text-white' => $size === 'lg',
    'px-4 py-1 text-xl font-medium rounded-full text-white' => $size === 'xl',
    'bg-primary' => $color === 'primary',
    'bg-secondary' => $color === 'secondary',
    'bg-success' => $color === 'success',
    'bg-danger' => $color === 'danger',
    'bg-warning' => $color === 'warning',
    'bg-info' => $color === 'info',
]) {{ $attributes->merge(['class' => '']) }} @if ($color === 'custom')
    style="background-color: {{ $colorValue }}; color: #fff;"
    @endif
    >{{ $slot }}</span>
