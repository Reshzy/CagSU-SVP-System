{{-- Single glass capsule: home + scroll (slides from behind home rightward inside the same bar).
     Show "back to top" when scroll exceeds the main navbar height (same threshold as glass top nav). --}}
<div
    x-data="{
        show: false,
        scrollThresholdPx: 64,
        captureMainNavHeight() {
            const el = document.getElementById('main-app-navigation');
            if (! el) {
                return;
            }
            const h = Math.ceil(el.getBoundingClientRect().height);
            if (h > 0) {
                this.scrollThresholdPx = h;
            }
        },
        syncScroll() {
            const root = document.scrollingElement ?? document.documentElement;
            const y = Math.max(0, root.scrollTop);
            this.show = y > this.scrollThresholdPx;
        },
        onResize() {
            const root = document.scrollingElement ?? document.documentElement;
            const y = Math.max(0, root.scrollTop);
            if (y <= this.scrollThresholdPx) {
                this.captureMainNavHeight();
            }
            this.syncScroll();
        },
    }"
    x-init="$nextTick(() => { captureMainNavHeight(); syncScroll(); }); $watch('show', (v) => { if (! v) { $nextTick(() => captureMainNavHeight()); } })"
    @scroll.window="syncScroll()"
    @resize.window.debounce.150ms="onResize()"
    class="pointer-events-none fixed inset-x-0 bottom-6 z-50 flex justify-center px-4"
>
    <nav
        class="pointer-events-auto relative overflow-hidden rounded-full border border-white/20 bg-white/[0.08] p-1.5 shadow-[0_10px_40px_-10px_rgba(0,0,0,0.15),inset_0_1px_0_0_rgba(255,255,255,0.55),inset_0_-1px_0_0_rgba(255,255,255,0.22)] backdrop-blur-2xl backdrop-saturate-150 before:pointer-events-none before:absolute before:inset-x-4 before:top-px before:h-px before:bg-gradient-to-r before:from-transparent before:via-white/80 before:to-transparent before:content-[''] after:pointer-events-none after:absolute after:inset-x-4 after:bottom-px after:h-px after:bg-gradient-to-r after:from-transparent after:via-white/45 after:to-transparent after:content-[''] dark:border-white/10 dark:bg-white/[0.06] dark:shadow-[0_10px_40px_-10px_rgba(0,0,0,0.5),inset_0_1px_0_0_rgba(255,255,255,0.12),inset_0_-1px_0_0_rgba(255,255,255,0.08)] dark:before:via-white/25 dark:after:via-white/15"
        aria-label="{{ __('Quick navigation') }}"
    >
        {{-- Expanding track: one surface; scroll stays inside so it reads as one navbar --}}
        <div
            class="relative overflow-hidden rounded-full transition-[width] duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)] motion-reduce:transition-none"
            :class="show ? 'w-[6.5rem]' : 'w-12'"
        >
            <div
                class="absolute left-0 top-0 z-10 flex h-12 w-12 items-center justify-center transition duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)] motion-reduce:transition-none"
                :class="show ? 'translate-x-[calc(3rem+0.5rem)] opacity-100 pointer-events-auto' : 'translate-x-0 opacity-0 pointer-events-none'"
            >
                <button
                    type="button"
                    aria-label="{{ __('Back to top') }}"
                    @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
                    class="flex h-12 w-12 items-center justify-center rounded-full text-cagsu-maroon transition hover:bg-white/15 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/50 focus-visible:ring-offset-0 active:scale-[0.96] dark:text-cagsu-yellow dark:hover:bg-white/10 dark:focus-visible:ring-white/30"
                >
                    <svg class="h-[1.35rem] w-[1.35rem]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                    </svg>
                </button>
            </div>
            <a
                href="{{ route('dashboard') }}"
                aria-label="{{ __('Go to dashboard') }}"
                class="relative z-20 flex h-12 w-12 items-center justify-center rounded-full text-cagsu-maroon transition hover:bg-white/15 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/50 focus-visible:ring-offset-0 active:scale-[0.96] dark:text-cagsu-yellow dark:hover:bg-white/10 dark:focus-visible:ring-white/30"
            >
                <svg class="h-[1.35rem] w-[1.35rem]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
            </a>
        </div>
    </nav>
</div>
