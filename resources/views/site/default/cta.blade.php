@if ($page->getMeta('has_cta'))
    <div>
        <div class="mx-auto max-w-7xl py-8 sm:px-6 sm:py-12 lg:px-8">
            <div
                class="relative isolate overflow-hidden bg-site-dark-primary px-6 py-24 text-center shadow-2xl sm:rounded-3xl sm:px-16">
                <h2 class="text-balance text-4xl font-semibold tracking-tight text-white sm:text-5xl">
                    {{ $page->getMeta('cta_title') }}</h2>
                <p class="mx-auto mt-6 max-w-xl text-pretty text-lg/8 text-gray-300">
                    {{ $page->getMeta('cta_description') }}</p>
                <div class="mt-10 flex items-center justify-center gap-x-6">
                    <a href="{{ $page->getMeta('cta_button_link') }}"
                        class="rounded-md bg-white px-3.5 py-2.5 text-sm font-semibold text-gray-900 shadow-sm hover:bg-gray-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">{{ $page->getMeta('cta_button_text') }}</a>
                </div>
                <svg viewBox="0 0 1024 1024"
                    class="absolute left-1/2 top-1/2 -z-10 size-[64rem] -translate-x-1/2 [mask-image:radial-gradient(closest-side,white,transparent)]"
                    aria-hidden="true">
                    <circle cx="512" cy="512" r="512" fill="url(#827591b1-ce8c-4110-b064-7cb85a0b1217)"
                        fill-opacity="0.7" />
                    <defs>
                        <radialGradient id="827591b1-ce8c-4110-b064-7cb85a0b1217">
                            <stop stop-color="#7775D6" />
                            <stop offset="1" stop-color="#E935C1" />
                        </radialGradient>
                    </defs>
                </svg>
            </div>
        </div>
    </div>
@endif
