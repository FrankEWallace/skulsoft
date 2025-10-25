<x-errors.layout>
    <p class="text-primary text-4xl font-extrabold sm:text-5xl">404</p>
    <div class="sm:ml-6">
        <div class="sm:border-l sm:border-gray-600 sm:pl-6">
            <h1 class="text-4xl font-extrabold tracking-tight text-gray-900 sm:text-5xl">
                {{ trans('general.errors.404_title') }}</h1>
            <p class="mt-1 text-base text-gray-500">{{ trans('general.errors.404_description') }}</p>
        </div>
    </div>
</x-errors.layout>
