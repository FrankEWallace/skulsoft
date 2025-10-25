<x-site.layout>
    <div class="mt-16 container mx-auto">
        <img src={{ url(config('config.assets.logo')) }} class="w-96" />

        <h1 class="mt-10 text-2xl font-bold mb-6">Download Format</h1>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            @foreach ($files as $file)
                <a href="{{ $file['url'] }}"
                    class="block p-4 bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 ease-in-out">
                    <span class="text-primary hover:text-primary-dark">{{ $file['name'] }}</span>
                </a>
            @endforeach
        </div>

        <div class="mt-10 flex justify-center">
            <a class="rounded bg-gray-800 px-4 py-2 text-gray-200" href="/">{{ trans('dashboard.dashboard') }}</a>
        </div>
    </div>
</x-site.layout>
