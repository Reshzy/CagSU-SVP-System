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
    <body class="font-sans antialiased text-gray-900 dark:text-gray-100">
        <div class="min-h-screen bg-gov-light dark:bg-gray-900">
            <div class="relative isolate overflow-hidden">
                <div class="absolute inset-0 pointer-events-none bg-gradient-to-br from-cagsu-maroon via-cagsu-maroon to-cagsu-orange opacity-10 dark:opacity-20"></div>
                <div class="absolute -top-24 left-1/2 pointer-events-none h-72 w-[42rem] -translate-x-1/2 rounded-full bg-cagsu-yellow/30 blur-3xl dark:bg-cagsu-yellow/10"></div>
                <div class="mx-auto flex min-h-screen max-w-7xl items-center justify-center px-4 py-10 sm:px-6 lg:px-8">
                    <div class="w-full max-w-lg">
                        <a href="/" class="flex items-center justify-center">
                            <x-application-logo class="h-12 w-auto" />
                        </a>

                        <div class="mt-6 rounded-xl border border-gray-200 bg-white shadow-lg dark:border-gray-800 dark:bg-gray-950">
                            <div class="h-1.5 w-full rounded-t-xl bg-cagsu-yellow"></div>
                            <div class="px-6 py-6 sm:px-8">
                                {{ $slot }}
                            </div>
                        </div>

                        <p class="mt-6 text-center text-xs text-gray-600 dark:text-gray-400">
                            © {{ now()->year }} {{ config('app.name') }}. All rights reserved.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
