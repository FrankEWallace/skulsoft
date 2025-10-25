<x-site.layout>
    <img class="h-48 w-full object-cover lg:h-72" src="/images/site-background.jpg" />

    <div class="-mt-16 flex w-full items-center justify-center">
        <div class="bg-site-primary flex h-32 w-32 items-center justify-center rounded-full text-5xl text-white">
            {{ Str::substr(config('app.name'), 0, 1) }}</div>
    </div>

    <h1 class="mt-6 text-center text-4xl">
        {{ config('app.name') }}
    </h1>
</x-site.layout>
