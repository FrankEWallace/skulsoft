<x-errors.layout>
    <div class="flex items-center">
        <p class="text-primary text-4xl font-extrabold sm:text-5xl">398</p>
        <div class="sm:ml-6">
            <div class="sm:border-l sm:border-gray-600 sm:pl-6">
                <h1 class="text-xl font-semibold tracking-tight text-gray-900 sm:text-2xl">{{ $exception->getMessage() }}
                </h1>
            </div>
        </div>
    </div>
</x-errors.layout>
