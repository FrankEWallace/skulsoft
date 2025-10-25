<header id="navbar" class="inset-0 z-40 w-full items-center bg-slate-100 transition-all">

    <div class="flex h-12 items-center justify-end bg-black px-10 text-gray-200">
        <div class="sm:container">
            <div class="flex justify-end sm:justify-between">

                <div class="hidden sm:block">
                    @if (config('config.general.app_email'))
                        Email: {{ config('config.general.app_email') }}
                    @endif
                    @if (config('config.general.app_phone'))
                        | Phone: {{ config('config.general.app_phone') }}
                    @endif
                </div>

                <div class="">
                    <a href="/app/payment">Online Fee Payment</a> | <a href="/app/online-registration">Online
                        Registration</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <nav class="flex items-center">
            <div class="h-20 flex items-center">
                <a href="{{ route('site.home') }}">
                    <img src="{{ config('config.assets.logo') }}" class="logo-dark h-12" alt="Logo Dark">
                    <img src="{{ config('config.assets.logo') }}" class="logo-light h-12" alt="Logo Light">
                </a>
            </div>

            <div class="ms-auto hidden lg:block">
                <ul class="navbar-nav flex items-center justify-center gap-x-2">

                    @foreach ($headerMenus as $menu)
                        @if ($menu->children->count())
                            <li class="nav-item">
                                <a href="javascript:void(0);"
                                    class="nav-link after:absolute after:inset-0 hover:after:-bottom-10"
                                    data-fc-trigger="hover" data-fc-target="{{ $menu->slug }}" data-fc-type="dropdown"
                                    data-fc-placement="bottom">
                                    {{ $menu->name }} <i class="fa-solid fa-angle-down ms-2 align-middle"></i>
                                </a>

                                <div id="{{ $menu->slug }}"
                                    class="fc-dropdown-open:opacity-100 fc-dropdown-open:translate-y-0 mt-4 hidden w-48 origin-center translate-y-3 space-y-1.5 rounded-lg border bg-white p-2 opacity-0 shadow-lg transition-all z-50">
                                    @foreach ($menu->children as $child)
                                        <div class="nav-item">
                                            @if ($child->is_external)
                                                <a class="nav-link" href="{{ $child->url }}">{{ $child->name }}</a>
                                            @else
                                                <a class="nav-link" href="{{ $child->url }}">{{ $child->name }}</a>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </li>
                        @else
                            <li class="nav-item">
                                @if ($menu->is_external)
                                    <a class="nav-link" href="{{ $menu->url }}">{{ $menu->name }}</a>
                                @else
                                    <a class="nav-link" href="{{ $menu->url }}">{{ $menu->name }}</a>
                                @endif
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>

            <div class="ms-3 hidden items-center lg:flex">
                <a href="/app/login" target="_blank"
                    class="bg-site-primary inline-flex items-center rounded px-4 py-2 text-sm text-white">Login</a>
            </div>

            <div class="ms-auto flex items-center px-2.5 lg:hidden">
                <button type="button" data-fc-target="mobileMenu" data-fc-type="offcanvas">
                    <i class="fa-solid fa-bars text-2xl text-gray-500"></i>
                </button>
            </div>
        </nav>
    </div>
</header>

<div id="mobileMenu"
    class="fc-offcanvas-open:translate-x-0 fixed end-0 top-0 z-50 hidden h-full w-full max-w-md translate-x-full transform border-s bg-white transition-all duration-200">
    <div class="flex h-full flex-col divide-y-2 divide-gray-200">
        <div class="flex items-center justify-between p-6">
            <a href="{{ route('site.home') }}">
                <img src="{{ config('config.assets.icon') }}" class="h-16" alt="Logo">
            </a>

            <button data-fc-dismiss class="flex items-center px-2">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <div class="h-full overflow-scroll p-6">
            <ul class="navbar-nav flex flex-col gap-2" data-fc-type="accordion">

                @foreach ($headerMenus as $menu)
                    @if ($menu->children->count())
                        <li class="nav-item">
                            <a href="javascript:void(0)" data-fc-type="collapse" class="nav-link">
                                {{ $menu->name }} <i
                                    class="fa-solid fa-angle-down fc-collapse-open:rotate-180 ms-auto align-middle transition-all"></i>
                            </a>

                            <ul class="hidden space-y-2 overflow-hidden transition-[height] duration-300">
                                @foreach ($menu->children as $child)
                                    <li class="nav-item mt-2">
                                        @if ($child->is_external)
                                            <a class="nav-link" href="{{ $child->url }}">{{ $child->name }}</a>
                                        @else
                                            <a class="nav-link" href="{{ $child->url }}">{{ $child->name }}</a>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @else
                        <li class="nav-item">
                            @if ($menu->is_external)
                                <a class="nav-link" href="{{ $menu->url }}">{{ $menu->name }}</a>
                            @else
                                <a class="nav-link" href="{{ $menu->url }}">{{ $menu->name }}</a>
                            @endif
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>

        <div class="flex items-center justify-center p-6">
            <a href="/app/login" target="_blank"
                class="bg-site-primary flex w-full items-center justify-center rounded p-3 text-sm text-white">Login</a>
        </div>
    </div>
</div>
