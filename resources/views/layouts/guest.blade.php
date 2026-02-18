<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        @if (isset($wide) && $wide)
            {{ $slot }}
        @else
            @php
                $hasTopbar = isset($topbar_title) && $topbar_title;
            @endphp

            @if ($hasTopbar)
                <div class="fixed inset-x-0 top-0 h-12 bg-[#0066CC] shadow-md z-50 flex items-center justify-center">
                    <span class="text-white" style="font-size: 18px; font-weight: 500;">
                        {{ $topbar_title }}
                    </span>
                </div>
            @endif

            <div class="flex flex-col items-center {{ $hasTopbar ? 'pt-8' : 'min-h-screen sm:justify-center pt-6 sm:pt-0' }} bg-[#FDFDFC] dark:bg-[#0a0a0a]">
                <div class="w-full sm:max-w-md {{ $hasTopbar ? 'mt-0' : 'mt-6' }} px-6 py-6 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg">
                    {{ $slot }}
                </div>
            </div>
        @endif
    </body>
</html>
