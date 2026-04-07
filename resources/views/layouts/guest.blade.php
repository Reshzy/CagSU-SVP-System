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

        <!-- CagSU Favicon -->
        <link rel="icon" type="image/x-icon" href="{{ asset('CSU_Modern2.ico') }}">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    @php
        $guestToastMessages = [];

        if (session('success')) {
            $guestToastMessages[] = [
                'type' => 'success',
                'message' => session('success'),
            ];
        }

        if (session('warning')) {
            $guestToastMessages[] = [
                'type' => 'warning',
                'message' => session('warning'),
            ];
        }

        if (session('status')) {
            $statusMessage = session('status');
            if ($statusMessage === 'verification-link-sent') {
                $statusMessage = __('A new verification link has been sent to the email address you provided during registration.');
            }

            $guestToastMessages[] = [
                'type' => 'info',
                'message' => $statusMessage,
            ];
        }

        if (session('error')) {
            $guestToastMessages[] = [
                'type' => 'error',
                'message' => session('error'),
            ];
        }

        if ($errors->any()) {
            foreach ($errors->all() as $errorMessage) {
                $guestToastMessages[] = [
                    'type' => 'error',
                    'message' => $errorMessage,
                ];
            }
        }
    @endphp

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

        <div
            x-data="guestToasts(@js($guestToastMessages))"
            class="pointer-events-none fixed right-4 top-4 z-50 flex w-full max-w-sm flex-col gap-2"
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
                    data-guest-toast
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
                            class="h-full rounded-full transition-[width] duration-75 linear"
                            :class="progressBarClasses(toast.type)"
                            :style="`width: ${toast.progress}%;`"
                        ></div>
                    </div>
                </div>
            </template>
        </div>

        <script>
            function guestToasts(initialToasts = []) {
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
                            progress: 100,
                            dismissTimeoutId: null,
                            progressRafId: null,
                            startedAtMs: null,
                        };

                        this.toasts.push(toast);
                        const startProgressDraining = (timestamp) => {
                            if (! toast.visible) {
                                return;
                            }

                            if (toast.startedAtMs === null) {
                                toast.startedAtMs = timestamp;
                            }

                            const elapsedMs = timestamp - toast.startedAtMs;
                            const remainingRatio = Math.max(0, 1 - (elapsedMs / this.dismissDelayMs));
                            toast.progress = Math.round(remainingRatio * 10000) / 100;

                            if (remainingRatio > 0) {
                                toast.progressRafId = window.requestAnimationFrame(startProgressDraining);
                            }
                        };

                        toast.progressRafId = window.requestAnimationFrame(startProgressDraining);
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

                        if (toast.progressRafId) {
                            window.cancelAnimationFrame(toast.progressRafId);
                            toast.progressRafId = null;
                        }

                        toast.progress = 0;
                        toast.visible = false;
                        window.setTimeout(() => {
                            this.toasts.forEach((item) => {
                                if (item.id !== id) {
                                    return;
                                }

                                if (item.dismissTimeoutId) {
                                    window.clearTimeout(item.dismissTimeoutId);
                                }

                                if (item.progressRafId) {
                                    window.cancelAnimationFrame(item.progressRafId);
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
                            success: 'bg-emerald-900/35',
                            error: 'bg-red-900/35',
                            warning: 'bg-amber-950/20',
                            info: 'bg-sky-900/35',
                        };

                        return palette[type] || palette.info;
                    },

                    progressBarClasses(type) {
                        const palette = {
                            success: 'bg-emerald-50',
                            error: 'bg-red-50',
                            warning: 'bg-amber-900',
                            info: 'bg-sky-50',
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
    </body>
</html>
