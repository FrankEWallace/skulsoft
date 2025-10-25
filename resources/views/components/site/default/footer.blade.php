<footer class="bg-gray-950 pb-5 pt-4">
    <div class="container">
        <div class="grid grid-cols-4 gap-14">
            <div class="col-span-4 text-center">
                <p class="mt-5 text-gray-300/80">{{ config('config.general.app_address') }}</p>
                <div class="flex flex-col gap-3">
                    @if (config('config.general.app_email'))
                        <div class="text-gray-300/80"><a
                                href="javascript:void(0);">{{ config('config.general.app_email') }}</a>
                        </div>
                    @endif
                    <div class="flex justify-center gap-3">
                        @if (config('config.social_network.facebook'))
                            <div>
                                <a class="hover:text-gray-200" href="{{ config('config.social_network.facebook') }}">
                                    <i class="fab fa-facebook fa-xl"></i>
                                </a>
                            </div>
                        @endif
                        @if (config('config.social_network.twitter'))
                            <div>
                                <a class="hover:text-gray-200" href="{{ config('config.social_network.twitter') }}">
                                    <i class="fab fa-twitter fa-xl"></i>
                                </a>
                            </div>
                        @endif
                        @if (config('config.social_network.youtube'))
                            <div>
                                <a class="hover:text-gray-200" href="{{ config('config.social_network.youtube') }}">
                                    <i class="fab fa-youtube fa-xl"></i>
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 flex justify-center space-x-2">
            @foreach ($footerMenus as $menu)
                <a href="{{ $menu->url }}" class="text-gray-300/80">{{ $menu->name }}</a>
            @endforeach
        </div>
        <div class="my-5 border-b"></div>
        <div class="text-center">
            <p class="text-sm text-gray-300/80">
                <script>
                    document.write(new Date().getFullYear())
                </script>&copy; {{ config('config.general.app_name') }}. All rights reserved.
                {{ config('config.system.footer_credit') }}
            </p>
        </div>
    </div>
</footer>

<button data-toggle="back-to-top"
    class="fixed bottom-5 end-5 z-10 flex h-9 w-9 items-center justify-center rounded-full border border-gray-500 bg-gray-200/20 text-center text-sm text-gray-200">
    <i class="fa-solid fa-arrow-up text-base"></i>
</button>
