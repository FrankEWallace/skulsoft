@props([
    'headers' => [],
])

<div class="-mx-6 mt-10 ring-1 ring-gray-300 sm:mx-0 sm:rounded-lg">
    <table class="min-w-full divide-y divide-gray-300">
        <thead>
            <tr>
                @foreach ($headers as $header)
                    <th scope="col" class="py-3.5 pl-6 pr-3 text-left text-sm font-semibold text-gray-900">
                        {{ Arr::get($header, 'label') }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>
