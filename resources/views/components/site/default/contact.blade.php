<div class="lg:flex align-items-center">
    <div class="lg:w-1/2">
        <div class="mb-6 relative bg-clip-border rounded-[0.1875rem]">
            <div class="py-12">
                @livewire('query')
            </div>
        </div>
    </div>

    @if (config('config.site.google_map_embed_url'))
        <div class="lg:w-5/12 ms-auto overflow-x-hidden">
            <div class="h-[520px]">
                <div class="h-100">
                    <iframe class="w-full h-[500px]" src="{{ config('config.site.google_map_embed_url') }}"
                        frameborder="0"></iframe>
                </div>
            </div>
        </div>
    @endif
</div>

<div class="mt-8 grid md:grid-cols-3 gap-6">
    <div class="flex items-center gap-4">
        <span class="flex items-center justify-center size-12 bg-site-primary rounded-lg relative shrink-0">
            <i class="fas fa-envelope fa-lg text-white"></i>
        </span>
        <div>
            <h5 class="text-base text-default-700">Email</h5>
            <a href="mailto:{{ config('config.general.app_email') }}"
                class="text-default-500 my-1">{{ config('config.general.app_email') }}</a>
        </div>
    </div>

    <div class="flex items-center gap-4">
        <span class="flex items-center justify-center size-12 bg-site-primary rounded-lg relative shrink-0">
            <i class="fas fa-phone fa-lg text-white"></i>
        </span>
        <div>
            <h5 class="text-base text-default-700">Phone</h5>
            <a href="tel:{{ config('config.general.app_phone') }}"
                class="text-default-500 my-1">{{ config('config.general.app_phone') }}</a>
        </div>
    </div>

    <div class="flex items-center gap-4">
        <span class="flex items-center justify-center size-12 bg-site-primary rounded-lg relative shrink-0">
            <i class="fas fa-map-marker-alt fa-lg text-white"></i>
        </span>
        <div>
            <h5 class="text-base text-default-700">Address</h5>
            <span
                class="flex-wrap">{{ collect([
                    config('config.general.app_address_line1'),
                    config('config.general.app_address_line2'),
                    config('config.general.app_city'),
                    config('config.general.app_state'),
                    config('config.general.app_zipcode'),
                    config('config.general.app_country'),
                ])->filter()->map(fn($item) => trim($item))->filter()->join(', ') }}</span>
        </div>
    </div>
</div>
