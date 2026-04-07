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
        <link rel="icon" type="image/x-icon" href="{{ asset('CSU_Modern2.ico') }}">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        @livewireStyles
    </head>
    @php
        $appToastMessages = [];

        if (session('success')) {
            $appToastMessages[] = [
                'type' => 'success',
                'message' => session('success'),
            ];
        }

        if (session('warning')) {
            $appToastMessages[] = [
                'type' => 'warning',
                'message' => session('warning'),
            ];
        }

        if (session('status')) {
            $appToastMessages[] = [
                'type' => 'info',
                'message' => session('status'),
            ];
        }

        if (session('error')) {
            $appToastMessages[] = [
                'type' => 'error',
                'message' => session('error'),
            ];
        }

        if ($errors->any()) {
            foreach ($errors->all() as $errorMessage) {
                $appToastMessages[] = [
                    'type' => 'error',
                    'message' => $errorMessage,
                ];
            }
        }
    @endphp

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
                                        {{ Auth::user()->position?->name ?? 'Staff' }} |
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
            <main class="pb-24">
                {{ $slot }}
            </main>
        </div>

        <div
            x-data="appToasts(@js($appToastMessages))"
            class="pointer-events-none fixed right-4 top-[calc(var(--app-sticky-header-offset)+1rem)] z-50 flex w-full max-w-sm flex-col gap-2"
        >
            <template x-for="toast in toasts" :key="toast.id">
                <div
                    x-show="toast.visible"
                    x-transition.opacity.duration.250ms
                    x-transition.scale.origin.top.right.duration.250ms
                    class="pointer-events-auto rounded-lg border px-4 py-3 shadow-lg backdrop-blur-sm"
                    :class="toastClasses(toast.type)"
                    role="status"
                    aria-live="polite"
                    data-app-toast
                >
                    <div class="flex items-start gap-3">
                        <div class="flex-1 text-sm font-medium" x-text="toast.message"></div>
                        <button
                            type="button"
                            @click="dismiss(toast.id)"
                            class="rounded p-0.5 opacity-80 transition hover:opacity-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-1"
                            :class="closeButtonClasses(toast.type)"
                        >
                            <span class="sr-only">Dismiss notification</span>
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>
        {{-- Stack for page-specific scripts pushed from views --}}
        @stack('scripts')

        <script>
            function appToasts(initialToasts = []) {
                return {
                    toasts: [],
                    nextToastId: 1,
                    dismissDelayMs: 5000,

                    init() {
                        initialToasts.forEach((toast) => {
                            this.addToast(toast.type, toast.message);
                        });

                        window.addEventListener('app-toast', (event) => {
                            const detail = event?.detail ?? {};
                            if (typeof detail === 'string') {
                                this.addToast('info', detail);

                                return;
                            }

                            this.addToast(detail.type ?? 'info', detail.message ?? '');
                        });
                    },

                    addToast(type, message) {
                        const toast = {
                            id: this.nextToastId++,
                            type: type || 'info',
                            message: message || '',
                            visible: true,
                        };

                        this.toasts.push(toast);
                        window.setTimeout(() => this.dismiss(toast.id), this.dismissDelayMs);
                    },

                    dismiss(id) {
                        const toast = this.toasts.find((item) => item.id === id);
                        if (!toast || !toast.visible) {
                            return;
                        }

                        toast.visible = false;
                        window.setTimeout(() => {
                            this.toasts = this.toasts.filter((item) => item.id !== id);
                        }, 260);
                    },

                    toastClasses(type) {
                        const palette = {
                            success: 'border-green-300 bg-green-50 text-green-900 dark:border-green-700 dark:bg-green-900/90 dark:text-green-100',
                            error: 'border-red-300 bg-red-50 text-red-900 dark:border-red-700 dark:bg-red-900/90 dark:text-red-100',
                            warning: 'border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-700 dark:bg-amber-900/90 dark:text-amber-100',
                            info: 'border-sky-300 bg-sky-50 text-sky-900 dark:border-sky-700 dark:bg-sky-900/90 dark:text-sky-100',
                        };

                        return palette[type] || palette.info;
                    },

                    closeButtonClasses(type) {
                        const palette = {
                            success: 'text-green-700 focus-visible:ring-green-500 dark:text-green-200',
                            error: 'text-red-700 focus-visible:ring-red-500 dark:text-red-200',
                            warning: 'text-amber-700 focus-visible:ring-amber-500 dark:text-amber-200',
                            info: 'text-sky-700 focus-visible:ring-sky-500 dark:text-sky-200',
                        };

                        return palette[type] || palette.info;
                    },
                };
            }

            if (typeof window.appToast !== 'function') {
                window.appToast = function (typeOrPayload = 'info', message = '') {
                    if (typeof typeOrPayload === 'string' && message === '') {
                        window.dispatchEvent(new CustomEvent('app-toast', {
                            detail: {
                                type: 'info',
                                message: typeOrPayload,
                            },
                        }));

                        return;
                    }

                    if (typeof typeOrPayload === 'object' && typeOrPayload !== null) {
                        window.dispatchEvent(new CustomEvent('app-toast', {
                            detail: {
                                type: typeOrPayload.type ?? 'info',
                                message: typeOrPayload.message ?? '',
                            },
                        }));

                        return;
                    }

                    window.dispatchEvent(new CustomEvent('app-toast', {
                        detail: {
                            type: typeOrPayload || 'info',
                            message: message || '',
                        },
                    }));
                };
            }
        </script>

        @include('layouts.bottom-navigation')

        @livewireScripts
    </body>
</html>
