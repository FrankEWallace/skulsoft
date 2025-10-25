<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ config('config.general.meta_description') }}">
    <meta name="keywords" content="{{ config('config.general.meta_keywords') }}">
    <meta name="author" content="{{ config('config.general.meta_author') }}">
    <title>{{ config('config.general.app_name', config('app.name', 'ScriptMint')) }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <link rel="icon" href="{{ config('config.assets.favicon') }}" type="image/png">

    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    @vite(['resources/js/site.js', 'resources/sass/site.scss'], 'site/build')
</head>

<body class="{{ config('config.layout.display') }}">
    <div class="flex min-h-screen items-center overflow-hidden bg-gray-800">
        <div class="mx-auto w-full max-w-screen-xl px-4 py-4 sm:px-6">
            <div class="rounded-lg bg-gray-200 px-4 py-16 sm:px-6 sm:py-24 md:grid md:place-items-center lg:px-8">
                <div class="mx-auto max-w-max">
                    <main class="sm:flex">
                        {{ $slot }}
                    </main>

                    <div class="mt-6 flex justify-center">
                        <a class="rounded bg-gray-800 px-4 py-2 text-gray-200"
                            href="{{ $url }}">{{ $actionText }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
