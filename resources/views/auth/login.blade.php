<x-guest-layout>
    @if (config('app.debug'))
        <x-quick-login-sidebar />
    @endif

    <div class="mb-6 text-center">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">Welcome back</h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Log in to continue to the SVP System.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input
                id="email"
                class="mt-1 block w-full"
                type="email"
                name="email"
                :value="old('email')"
                required
                autofocus
                autocomplete="username"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input
                id="password"
                class="mt-1 block w-full"
                type="password"
                name="password"
                required
                autocomplete="current-password"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between gap-4">
            <label for="remember_me" class="inline-flex items-center gap-2">
                <input
                    id="remember_me"
                    type="checkbox"
                    class="rounded border-gray-300 text-cagsu-maroon shadow-sm focus:ring-cagsu-maroon dark:border-gray-700 dark:bg-gray-900"
                    name="remember"
                >
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a
                    class="text-sm font-medium text-cagsu-maroon underline-offset-4 hover:underline focus:outline-none focus:ring-2 focus:ring-cagsu-yellow focus:ring-offset-2 dark:text-cagsu-yellow dark:focus:ring-offset-gray-950"
                    href="{{ route('password.request') }}"
                >
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        </div>

        <x-primary-button class="w-full justify-center">
            {{ __('Log in') }}
        </x-primary-button>

        <p class="pt-2 text-center text-sm text-gray-600 dark:text-gray-400">
            Don’t have an account?
            <a
                href="{{ route('register') }}"
                class="font-medium text-cagsu-maroon underline-offset-4 hover:underline focus:outline-none focus:ring-2 focus:ring-cagsu-yellow focus:ring-offset-2 dark:text-cagsu-yellow dark:focus:ring-offset-gray-950"
            >
                Register
            </a>
        </p>
    </form>
</x-guest-layout>