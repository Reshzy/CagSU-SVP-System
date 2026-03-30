<x-guest-layout>
    <div class="mb-6">
        <a
            href="{{ route('register') }}"
            class="inline-flex items-center gap-1 text-sm font-medium text-cagsu-maroon underline-offset-4 hover:underline dark:text-cagsu-yellow"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back to registration
        </a>
    </div>

    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">Request a New Department</h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            If your department or college isn't listed in the registration form, you can submit a request here. The CEO will review and approve it before it becomes available.
        </p>
    </div>

    <div class="mb-5 rounded-md border border-cagsu-yellow/60 bg-cagsu-yellow/10 px-4 py-3 text-sm text-gray-700 dark:border-cagsu-yellow/30 dark:text-gray-300">
        <strong class="font-semibold text-cagsu-maroon dark:text-cagsu-yellow">How this works:</strong>
        Submit the form below with your department's details and your email address. Once the CEO approves it, the department will appear in the registration dropdown and you'll be able to complete your registration.
    </div>

    <form method="POST" action="{{ route('register.request-department.store') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="name" :value="__('Department / College Name')" />
            <x-text-input
                id="name"
                class="mt-1 block w-full"
                type="text"
                name="name"
                :value="old('name')"
                required
                autofocus
                placeholder="e.g. College of Engineering"
            />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="code" :value="__('Short Code')" />
            <x-text-input
                id="code"
                class="mt-1 block w-full uppercase"
                type="text"
                name="code"
                :value="old('code')"
                required
                maxlength="10"
                placeholder="e.g. COE"
            />
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Up to 10 letters/numbers. This must be unique.</p>
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="description" :value="__('Description')" />
            <textarea
                id="description"
                name="description"
                rows="3"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-cagsu-yellow dark:focus:ring-cagsu-yellow"
                placeholder="Brief description of the department (optional)"
            >{{ old('description') }}</textarea>
            <x-input-error :messages="$errors->get('description')" class="mt-2" />
        </div>

        <div class="border-t border-gray-100 dark:border-gray-800"></div>

        <div>
            <x-input-label for="requester_email" :value="__('Your Email Address')" />
            <x-text-input
                id="requester_email"
                class="mt-1 block w-full"
                type="email"
                name="requester_email"
                :value="old('requester_email')"
                required
                placeholder="you@example.com"
            />
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">We'll use this to link your request. You can use the same email when registering.</p>
            <x-input-error :messages="$errors->get('requester_email')" class="mt-2" />
        </div>

        <x-primary-button class="w-full justify-center">
            {{ __('Submit Department Request') }}
        </x-primary-button>
    </form>
</x-guest-layout>
