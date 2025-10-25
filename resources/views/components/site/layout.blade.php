<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Application by ScriptMint">
    <meta name="author" content="ScriptMint">
    <title>{{ config('config.general.app_name', config('app.name', 'ScriptMint')) }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <link rel="icon" href="{{ config('config.assets.favicon') }}" type="image/png">

    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    @vite(['resources/js/site.js', 'resources/sass/site.scss'], 'site/build')
    @livewireStyles
</head>

<body class="theme-{{ config('config.site.color_scheme', 'default') }}">

    {{ $slot }}

    @includeIf('components.site.custom.footer')

    @livewireScriptConfig
</body>

</html>
