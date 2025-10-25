@props([
    'size' => 'sm',
    'as' => 'button',
    'block' => false,
    'color' => 'primary',
])

@if ($as == 'link')
    <a {{ $attributes }}
        {{ $attributes->class([
            'justify-center shadow-sm font-medium rounded-md text-white  focus:outline-none focus:ring-2 focus:ring-offset-2 text-sm',
            'py-2 px-4' => $size == 'sm',
            'py-3 px-4' => $size == 'md',
            'w-full block text-center' => $block,
            'inline-flex' => !$block,
            'bg-primary hover:bg-light-primary focus:ring-primary' => $color == 'primary',
            'bg-secondary hover:bg-light-secondary focus:ring-secondary' => $color == 'secondary',
            'bg-success hover:bg-light-success focus:ring-success' => $color == 'success',
            'bg-danger hover:bg-light-danger focus:ring-danger' => $color == 'danger',
            'bg-warning hover:bg-light-warning focus:ring-warning' => $color == 'warning',
            'bg-info hover:bg-light-info focus:ring-info' => $color == 'info',
        ]) }}>
        {{ $slot }}
    </a>
@else
    <button {{ $attributes }}
        {{ $attributes->class([
            'justify-center shadow-sm font-medium rounded-md text-white focus:outline-none focus:ring-2 focus:ring-offset-2 text-sm',
            'py-2 px-4' => $size == 'sm',
            'py-3 px-4' => $size == 'md',
            'w-full block' => $block,
            'inline-flex' => !$block,
            'bg-primary hover:bg-light-primary focus:ring-primary' => $color == 'primary',
            'bg-secondary hover:bg-light-secondary focus:ring-secondary' => $color == 'secondary',
            'bg-success hover:bg-light-success focus:ring-success' => $color == 'success',
            'bg-danger hover:bg-light-danger focus:ring-danger' => $color == 'danger',
            'bg-warning hover:bg-light-warning focus:ring-warning' => $color == 'warning',
            'bg-info hover:bg-light-info focus:ring-info' => $color == 'info',
        ]) }}>
        {{ $slot }}
    </button>
@endif
