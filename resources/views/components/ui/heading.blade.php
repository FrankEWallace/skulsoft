@props(['heading' => 'h2'])

@if ($heading == 'h1')
    <h1 {{ $attributes->merge(['class' => 'text-4xl font-bold text-gray-800 dark:text-gray-400']) }}>
        {{ $slot }}
    </h1>
@elseif ($heading == 'h2')
    <h2 {{ $attributes->merge(['class' => 'text-2xl font-bold text-gray-800 dark:text-gray-400']) }}>
        {{ $slot }}
    </h2>
@elseif ($heading == 'h3')
    <h3 {{ $attributes->merge(['class' => 'text-xl font-semibold text-gray-800 dark:text-gray-400']) }}>
        {{ $slot }}
    </h3>
@elseif ($heading == 'h4')
    <h4 {{ $attributes->merge(['class' => 'text-xl font-semibold text-gray-800 dark:text-gray-400']) }}>
        {{ $slot }}
    </h4>
@elseif ($heading == 'h5')
    <h5 {{ $attributes->merge(['class' => 'text-lg font-medium text-gray-800 dark:text-gray-400']) }}>
        {{ $slot }}
    </h5>
@elseif ($heading == 'h6')
    <h6 {{ $attributes->merge(['class' => 'text-md font-medium text-gray-800 dark:text-gray-400']) }}>
        {{ $slot }}
    </h6>
@endif
