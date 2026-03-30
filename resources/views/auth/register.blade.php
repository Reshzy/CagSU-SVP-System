<x-guest-layout>
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">Create an account</h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Register to access the CagSU SVP System. Your account requires CEO approval before you can log in.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf

        {{-- Personal Information --}}
        <div>
            <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest text-cagsu-maroon dark:text-cagsu-yellow">Personal Information</h2>
            <div class="space-y-4">
                <div>
                    <x-input-label for="name" :value="__('Full Name')" />
                    <x-text-input
                        id="name"
                        class="mt-1 block w-full"
                        type="text"
                        name="name"
                        :value="old('name')"
                        required
                        autofocus
                        autocomplete="name"
                    />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="email" :value="__('Email Address')" />
                    <x-text-input
                        id="email"
                        class="mt-1 block w-full"
                        type="email"
                        name="email"
                        :value="old('email')"
                        required
                        autocomplete="username"
                    />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="employee_id" :value="__('Employee ID')" />
                        <x-text-input
                            id="employee_id"
                            class="mt-1 block w-full"
                            type="text"
                            name="employee_id"
                            :value="old('employee_id')"
                            autocomplete="off"
                        />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Optional</p>
                        <x-input-error :messages="$errors->get('employee_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="phone" :value="__('Phone Number')" />
                        <x-text-input
                            id="phone"
                            class="mt-1 block w-full"
                            type="text"
                            name="phone"
                            :value="old('phone')"
                            autocomplete="tel"
                        />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Optional</p>
                        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                    </div>
                </div>
            </div>
        </div>

        <div class="border-t border-gray-100 dark:border-gray-800"></div>

        {{-- Department & Position --}}
        <div>
            <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest text-cagsu-maroon dark:text-cagsu-yellow">Department & Position</h2>
            <div class="space-y-4">
                <div>
                    <x-input-label for="department_id" :value="__('Department')" />
                    <select
                        id="department_id"
                        name="department_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-cagsu-yellow dark:focus:ring-cagsu-yellow"
                        required
                    >
                        <option value="">— Select department —</option>
                        @isset($departments)
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" @selected(old('department_id') == $dept->id)>{{ $dept->name }}</option>
                            @endforeach
                        @endisset
                    </select>
                    <x-input-error :messages="$errors->get('department_id')" class="mt-2" />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Don't see your department?
                        <a
                            href="{{ route('register.request-department') }}"
                            class="font-medium text-cagsu-maroon underline-offset-4 hover:underline dark:text-cagsu-yellow"
                        >Request a new department</a>
                    </p>
                </div>

                <div>
                    <x-input-label for="position_id" :value="__('Position')" />
                    <select
                        id="position_id"
                        name="position_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-cagsu-yellow dark:focus:ring-cagsu-yellow"
                        required
                    >
                        <option value="">— Select position —</option>
                        @isset($positions)
                            @foreach($positions as $pos)
                                <option value="{{ $pos->id }}" @selected(old('position_id') == $pos->id)>{{ $pos->name }}</option>
                            @endforeach
                        @endisset
                    </select>
                    <x-input-error :messages="$errors->get('position_id')" class="mt-2" />
                </div>
            </div>
        </div>

        <div class="border-t border-gray-100 dark:border-gray-800"></div>

        {{-- Credentials & Verification --}}
        <div>
            <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest text-cagsu-maroon dark:text-cagsu-yellow">Password & Verification</h2>
            <div class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="password" :value="__('Password')" />
                        <x-text-input
                            id="password"
                            class="mt-1 block w-full"
                            type="password"
                            name="password"
                            required
                            autocomplete="new-password"
                        />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                        <x-text-input
                            id="password_confirmation"
                            class="mt-1 block w-full"
                            type="password"
                            name="password_confirmation"
                            required
                            autocomplete="new-password"
                        />
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="id_proof" :value="__('University ID or Valid Government ID')" />
                    <input
                        id="id_proof"
                        type="file"
                        name="id_proof"
                        accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                        required
                        class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm file:mr-3 file:rounded file:border-0 file:bg-cagsu-maroon file:px-3 file:py-1 file:text-xs file:font-semibold file:text-white hover:file:bg-cagsu-orange focus:outline-none focus:ring-2 focus:ring-cagsu-maroon focus:ring-offset-2 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                    />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Max 10 MB. Accepted formats: PDF, JPG, PNG, DOC, DOCX.</p>
                    <x-input-error :messages="$errors->get('id_proof')" class="mt-2" />
                </div>
            </div>
        </div>

        <div class="border-t border-gray-100 dark:border-gray-800"></div>

        <div class="rounded-md border border-cagsu-yellow/60 bg-cagsu-yellow/10 px-4 py-3 text-sm text-gray-700 dark:border-cagsu-yellow/30 dark:text-gray-300">
            <strong class="font-semibold text-cagsu-maroon dark:text-cagsu-yellow">What happens next?</strong>
            Your registration will be reviewed and approved by the CEO before your account is activated. You will be able to log in once approved.
        </div>

        <x-primary-button class="w-full justify-center">
            {{ __('Submit Registration') }}
        </x-primary-button>

        <p class="text-center text-sm text-gray-600 dark:text-gray-400">
            Already have an account?
            <a
                href="{{ route('login') }}"
                class="font-medium text-cagsu-maroon underline-offset-4 hover:underline focus:outline-none focus:ring-2 focus:ring-cagsu-yellow focus:ring-offset-2 dark:text-cagsu-yellow dark:focus:ring-offset-gray-950"
            >
                Log in
            </a>
        </p>
    </form>
</x-guest-layout>
