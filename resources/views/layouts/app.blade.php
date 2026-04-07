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
                    x-transition:enter="transform transition ease-out duration-300"
                    x-transition:enter-start="translate-x-full opacity-0"
                    x-transition:enter-end="translate-x-0 opacity-100"
                    x-transition:leave="transform transition ease-in duration-220"
                    x-transition:leave-start="translate-x-0 opacity-100"
                    x-transition:leave-end="translate-x-full opacity-0"
                    class="pointer-events-auto rounded-lg border-2 px-4 py-3 shadow-2xl"
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
                            class="rounded p-0.5 opacity-95 transition hover:opacity-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-1"
                            :class="closeButtonClasses(toast.type)"
                        >
                            <span class="sr-only">Dismiss notification</span>
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full" :class="progressTrackClasses(toast.type)">
                        <div
                            class="h-full w-full rounded-full"
                            :class="progressBarClasses(toast.type)"
                            :style="`animation: app-toast-drain ${dismissDelayMs}ms linear forwards;`"
                        ></div>
                    </div>
                </div>
            </template>
        </div>

        @if (config('app.debug'))
            <button
                type="button"
                class="fixed bottom-24 right-4 z-40 rounded-md bg-slate-900 px-3 py-2 text-xs font-semibold text-white shadow-lg transition hover:bg-slate-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-500 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white"
                x-data
                x-on:click="window.appToast({ type: 'info', message: 'Preview toast: 5-second progress drain.' })"
            >
                Test Toast
            </button>
        @endif
        {{-- Stack for page-specific scripts pushed from views --}}
        @stack('scripts')

        <style>
            @keyframes app-toast-drain {
                from {
                    width: 100%;
                }
                to {
                    width: 0%;
                }
            }
        </style>

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
                            visible: false,
                            dismissTimeoutId: null,
                        };

                        this.toasts.push(toast);
                        window.requestAnimationFrame(() => {
                            const toastToShow = this.toasts.find((item) => item.id === toast.id);
                            if (! toastToShow) {
                                return;
                            }

                            toastToShow.visible = true;
                        });
                        toast.dismissTimeoutId = window.setTimeout(() => this.dismiss(toast.id), this.dismissDelayMs);
                    },

                    dismiss(id) {
                        const toast = this.toasts.find((item) => item.id === id);
                        if (! toast || ! toast.visible) {
                            return;
                        }

                        if (toast.dismissTimeoutId) {
                            window.clearTimeout(toast.dismissTimeoutId);
                            toast.dismissTimeoutId = null;
                        }

                        toast.visible = false;
                        window.setTimeout(() => {
                            this.toasts.forEach((item) => {
                                if (item.id !== id) {
                                    return;
                                }

                                if (item.dismissTimeoutId) {
                                    window.clearTimeout(item.dismissTimeoutId);
                                }
                            });

                            this.toasts = this.toasts.filter((item) => item.id !== id);
                        }, 260);
                    },

                    toastClasses(type) {
                        const palette = {
                            success: 'border-emerald-200 bg-emerald-700 text-emerald-50 dark:border-emerald-100 dark:bg-emerald-600 dark:text-white',
                            error: 'border-red-200 bg-red-700 text-red-50 dark:border-red-100 dark:bg-red-600 dark:text-white',
                            warning: 'border-amber-100 bg-amber-400 text-amber-950 dark:border-amber-50 dark:bg-amber-300 dark:text-amber-950',
                            info: 'border-sky-200 bg-sky-700 text-sky-50 dark:border-sky-100 dark:bg-sky-600 dark:text-white',
                        };

                        return palette[type] || palette.info;
                    },

                    closeButtonClasses(type) {
                        const palette = {
                            success: 'text-emerald-50 focus-visible:ring-emerald-100 focus-visible:ring-offset-emerald-700 dark:text-white dark:focus-visible:ring-emerald-50 dark:focus-visible:ring-offset-emerald-600',
                            error: 'text-red-50 focus-visible:ring-red-100 focus-visible:ring-offset-red-700 dark:text-white dark:focus-visible:ring-red-50 dark:focus-visible:ring-offset-red-600',
                            warning: 'text-amber-950 focus-visible:ring-amber-900 focus-visible:ring-offset-amber-300 dark:text-amber-950 dark:focus-visible:ring-amber-900 dark:focus-visible:ring-offset-amber-300',
                            info: 'text-sky-50 focus-visible:ring-sky-100 focus-visible:ring-offset-sky-700 dark:text-white dark:focus-visible:ring-sky-50 dark:focus-visible:ring-offset-sky-600',
                        };

                        return palette[type] || palette.info;
                    },

                    progressTrackClasses(type) {
                        const palette = {
                            success: 'bg-emerald-950/30',
                            error: 'bg-red-950/30',
                            warning: 'bg-amber-950/30',
                            info: 'bg-sky-950/30',
                        };

                        return palette[type] || palette.info;
                    },

                    progressBarClasses(type) {
                        const palette = {
                            success: 'bg-emerald-200',
                            error: 'bg-red-200',
                            warning: 'bg-amber-900',
                            info: 'bg-sky-200',
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
