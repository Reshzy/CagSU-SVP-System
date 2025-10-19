<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'CagSU SVP System') }} - @yield('title', 'Supply, Vendor & Procurement')</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        
        <!-- CagSU Favicon -->
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gov-light">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow border-b-4 border-cagsu-yellow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        <div class="flex items-center justify-between">
                            <div>
                                {{ $header }}
                                @auth
                                    <p class="text-sm text-gray-600 mt-1">
                                        {{ Auth::user()->department ? Auth::user()->department->name : 'No Department' }} | 
                                        {{ Auth::user()->position ?? 'Staff' }} |
                                        <span class="font-medium text-cagsu-maroon">{{ Auth::user()->getPrimarySVPRole() }}</span>
                                    </p>
                                @endauth
                            </div>
                            @auth
                                <div class="text-right text-sm text-gray-500">
                                    <div>{{ now()->format('F d, Y') }}</div>
                                    <div>{{ now()->format('l, g:i A') }}</div>
                                </div>
                            @endauth
                        </div>
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
        {{-- Stack for page-specific scripts pushed from views --}}
        @stack('scripts')
    </body>
</html>
