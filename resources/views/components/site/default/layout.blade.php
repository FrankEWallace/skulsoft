@props(['metaTitle', 'metaDescription', 'metaKeywords'])

<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="keywords" content="{{ $metaKeywords }}">
    <meta name="author" content="FW Technologies">
    <title>{{ $metaTitle ?? config('config.general.app_name', config('app.name', 'SkulSoft')) }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <link rel="icon" href="{{ config('config.assets.favicon') }}" type="image/png">

    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    @vite(['resources/js/site.js', 'resources/sass/site.scss'], 'site/build')
    @livewireStyles
</head>

<body class="theme-{{ config('config.site.color_scheme', 'default') }}">

    <x-site.header />

    {{ $slot }}

    <x-site.footer />

    <div class="fixed bottom-40 right-6 flex flex-col gap-3 z-50">
        @if (config('config.social_network.facebook'))
            <a href="{{ config('config.social_network.facebook') }}"
                class="flex items-center justify-center w-12 h-12 rounded-full bg-[#1877F2] text-white transition-all duration-200 hover:bg-[#0d6efd] hover:scale-110 transform border-2 border-white/20 hover:border-white/40">
                <i class="fa-brands fa-facebook-f"></i>
            </a>
        @endif
        @if (config('config.social_network.twitter'))
            <a href="{{ config('config.social_network.twitter') }}"
                class="flex items-center justify-center w-12 h-12 rounded-full bg-[#1DA1F2] text-white transition-all duration-200 hover:bg-[#0d95e8] hover:scale-110 transform border-2 border-white/20 hover:border-white/40">
                <i class="fa-brands fa-twitter"></i>
            </a>
        @endif
        @if (config('config.social_network.linkedin'))
            <a href="{{ config('config.social_network.linkedin') }}"
                class="flex items-center justify-center w-12 h-12 rounded-full bg-[#0A66C2] text-white transition-all duration-200 hover:bg-[#094c8f] hover:scale-110 transform border-2 border-white/20 hover:border-white/40">
                <i class="fa-brands fa-linkedin-in"></i>
            </a>
        @endif
        @if (config('config.social_network.youtube'))
            <a href="{{ config('config.social_network.youtube') }}"
                class="flex items-center justify-center w-12 h-12 rounded-full bg-[#FF0000] text-white transition-all duration-200 hover:bg-[#cc0000] hover:scale-110 transform border-2 border-white/20 hover:border-white/40">
                <i class="fa-brands fa-youtube"></i>
            </a>
        @endif
        @if (config('config.social_network.google'))
            <a href="{{ config('config.social_network.google') }}"
                class="flex items-center justify-center w-12 h-12 rounded-full bg-[#4285F4] text-white transition-all duration-200 hover:bg-[#3367d6] hover:scale-110 transform border-2 border-white/20 hover:border-white/40">
                <i class="fa-brands fa-google"></i>
            </a>
        @endif
        @if (config('config.social_network.github'))
            <a href="{{ config('config.social_network.github') }}"
                class="flex items-center justify-center w-12 h-12 rounded-full bg-[#333333] text-white transition-all duration-200 hover:bg-[#24292e] hover:scale-110 transform border-2 border-white/20 hover:border-white/40">
                <i class="fa-brands fa-github"></i>
            </a>
        @endif
    </div>

    @livewireScriptConfig
</body>

</html>
