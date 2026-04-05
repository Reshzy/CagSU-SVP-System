<x-guest-layout>
    <div
        x-data="registerWizard({
            old: @js([
                'name' => old('name'),
                'email' => old('email'),
                'employee_id' => old('employee_id'),
                'phone' => old('phone'),
                'department_id' => old('department_id'),
                'position_id' => old('position_id', $defaultPositionId ?? null),
                'password' => old('password'),
                'password_confirmation' => old('password_confirmation'),
            ]),
            defaults: @js([
                'position_id' => $defaultPositionId ?? null,
            ]),
            errors: @js($errors->keys()),
        })"
        x-init="init()"
        @department-changed.window="form.department_id = $event.detail.value; validateField('department_id')"
        class="space-y-6"
    >
        <div class="text-center">
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">Create an account</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Register to access the CagSU SVP System. Your account requires CEO approval before you can log in.</p>
        </div>

        <div class="space-y-3">
            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                <p class="font-medium" x-text="`Step ${step} of 4`"></p>
                <div class="flex items-center gap-3">
                    <p
                        class="text-emerald-600 dark:text-emerald-400"
                        x-cloak
                        x-show="savedMessageVisible"
                        x-transition.opacity.duration.200ms
                    >
                        Saved
                    </p>
                    <button
                        type="button"
                        class="font-medium text-cagsu-maroon underline-offset-4 hover:underline dark:text-cagsu-yellow"
                        @click="clearDraft(true)"
                    >
                        Clear saved data
                    </button>
                </div>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800">
                <div
                    class="h-full rounded-full bg-cagsu-maroon transition-all duration-300 dark:bg-cagsu-yellow"
                    :style="`width: ${progressPercent}%`"
                ></div>
            </div>
            <div class="grid grid-cols-4 gap-2 text-[11px] font-medium uppercase tracking-wide">
                <button type="button" class="rounded px-2 py-1" :class="stepButtonClass(1)" @click="goToStep(1)">Personal</button>
                <button type="button" class="rounded px-2 py-1" :class="stepButtonClass(2)" @click="goToStep(2)">Department</button>
                <button type="button" class="rounded px-2 py-1" :class="stepButtonClass(3)" @click="goToStep(3)">Security</button>
                <button type="button" class="rounded px-2 py-1" :class="stepButtonClass(4)" @click="goToStep(4)">Review</button>
            </div>
        </div>

        <form
            method="POST"
            action="{{ route('register') }}"
            enctype="multipart/form-data"
            class="space-y-5"
            @keydown.enter.prevent="handleEnter($event)"
            @submit="prepareSubmit($event)"
        >
            @csrf

            <div class="register-step-panel min-h-80" style="view-transition-name: register-step-panel;">
                <section x-cloak x-show="step === 1">
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
                                x-model.trim="form.name"
                                @input="validateField('name')"
                                @blur="applyTitleCase('name')"
                            />
                            <p class="mt-1 text-xs text-red-600" x-show="fieldMessage('name').type === 'error'" x-text="fieldMessage('name').text"></p>
                            <p class="mt-1 text-xs text-emerald-600" x-show="fieldMessage('name').type === 'success'" x-text="fieldMessage('name').text"></p>
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
                                x-model.trim="form.email"
                                @input="validateField('email')"
                            />
                            <p class="mt-1 text-xs text-red-600" x-show="fieldMessage('email').type === 'error'" x-text="fieldMessage('email').text"></p>
                            <p class="mt-1 text-xs text-emerald-600" x-show="fieldMessage('email').type === 'success'" x-text="fieldMessage('email').text"></p>
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
                                    x-model.trim="form.employee_id"
                                    @input="saveDraft()"
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
                                    x-model.trim="form.phone"
                                    @input="validateField('phone')"
                                />
                                <p class="mt-1 text-xs text-red-600" x-show="fieldMessage('phone').type === 'error'" x-text="fieldMessage('phone').text"></p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Optional</p>
                                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </section>

                <section x-cloak x-show="step === 2" class="flex min-h-80 flex-col">
                    <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest text-cagsu-maroon dark:text-cagsu-yellow">Department & Position</h2>
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="department_id" :value="__('Department')" />
                            <livewire:auth.register-department-select :initial-department-id="(string) old('department_id')" />
                            <p class="mt-1 text-xs text-red-600" x-show="fieldMessage('department_id').type === 'error'" x-text="fieldMessage('department_id').text"></p>
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
                                x-model="form.position_id"
                                @change="validateField('position_id')"
                            >
                                <option value="">— Select position —</option>
                                @isset($positions)
                                    @foreach($positions as $pos)
                                        <option value="{{ $pos->id }}" @selected(old('position_id') == $pos->id)>{{ $pos->name }}</option>
                                    @endforeach
                                @endisset
                            </select>
                            <p class="mt-1 text-xs text-red-600" x-show="fieldMessage('position_id').type === 'error'" x-text="fieldMessage('position_id').text"></p>
                            <x-input-error :messages="$errors->get('position_id')" class="mt-2" />
                        </div>
                    </div>
                    <div aria-hidden="true" class="register-gap-pattern mt-6 flex-1 rounded-xl border border-white/40 dark:border-white/10"></div>
                </section>

                <section x-cloak x-show="step === 3" class="flex min-h-80 flex-col">
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
                                    x-model="form.password"
                                    @input="validateField('password')"
                                />
                                <p class="mt-1 text-xs text-red-600" x-show="fieldMessage('password').type === 'error'" x-text="fieldMessage('password').text"></p>
                                <p class="mt-1 text-xs text-emerald-600" x-show="fieldMessage('password').type === 'success'" x-text="fieldMessage('password').text"></p>
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
                                    x-model="form.password_confirmation"
                                    @input="validateField('password_confirmation')"
                                />
                                <p class="mt-1 text-xs text-red-600" x-show="fieldMessage('password_confirmation').type === 'error'" x-text="fieldMessage('password_confirmation').text"></p>
                                <p class="mt-1 text-xs text-emerald-600" x-show="fieldMessage('password_confirmation').type === 'success'" x-text="fieldMessage('password_confirmation').text"></p>
                                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="id_proof" :value="__('University ID (Front & Back) or Valid Government ID')" />
                            <input
                                id="id_proof"
                                type="file"
                                name="id_proof[]"
                                accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf"
                                multiple
                                required
                                class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm file:mr-3 file:rounded file:border-0 file:bg-cagsu-maroon file:px-3 file:py-1 file:text-xs file:font-semibold file:text-white hover:file:bg-cagsu-orange focus:outline-none focus:ring-2 focus:ring-cagsu-maroon focus:ring-offset-2 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                @change="handleIdProofChange($event)"
                            />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Upload identification files such as JPG, PNG, WEBP, or PDF. Max 10 MB each.</p>
                            <p
                                class="mt-1 text-xs font-medium text-red-600 dark:text-red-400"
                                role="alert"
                                aria-live="polite"
                                x-cloak
                                x-show="attemptedReview && !hasIdProofSelected()"
                            >
                                Identification files are required to continue to Review.
                            </p>
                            <x-input-error :messages="$errors->get('id_proof')" class="mt-2" />
                            <x-input-error :messages="$errors->get('id_proof.*')" class="mt-2" />
                        </div>
                    </div>
                    <div aria-hidden="true" class="register-gap-pattern-security mt-6 flex-1 rounded-xl border border-white/40 dark:border-white/10"></div>
                </section>

                <section x-cloak x-show="step === 4">
                    <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest text-cagsu-maroon dark:text-cagsu-yellow">Review & Submit</h2>
                    <div class="space-y-4 text-sm">
                        <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                            <div class="mb-2 flex items-center justify-between">
                                <h3 class="font-semibold text-gray-800 dark:text-gray-200">Personal Information</h3>
                                <button type="button" class="text-xs font-medium text-cagsu-maroon hover:underline dark:text-cagsu-yellow" @click="goToStep(1)">Edit</button>
                            </div>
                            <dl class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                <div><dt class="text-xs text-gray-500">Name</dt><dd x-text="form.name || '—'"></dd></div>
                                <div><dt class="text-xs text-gray-500">Email</dt><dd x-text="form.email || '—'"></dd></div>
                                <div><dt class="text-xs text-gray-500">Employee ID</dt><dd x-text="form.employee_id || '—'"></dd></div>
                                <div><dt class="text-xs text-gray-500">Phone</dt><dd x-text="form.phone || '—'"></dd></div>
                            </dl>
                        </div>

                        <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                            <div class="mb-2 flex items-center justify-between">
                                <h3 class="font-semibold text-gray-800 dark:text-gray-200">Department & Position</h3>
                                <button type="button" class="text-xs font-medium text-cagsu-maroon hover:underline dark:text-cagsu-yellow" @click="goToStep(2)">Edit</button>
                            </div>
                            <dl class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                <div><dt class="text-xs text-gray-500">Department</dt><dd x-text="selectedText('department_id') || '—'"></dd></div>
                                <div><dt class="text-xs text-gray-500">Position</dt><dd x-text="selectedText('position_id') || '—'"></dd></div>
                            </dl>
                        </div>

                        <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                            <div class="mb-2 flex items-center justify-between">
                                <h3 class="font-semibold text-gray-800 dark:text-gray-200">Password & Verification</h3>
                                <button type="button" class="text-xs font-medium text-cagsu-maroon hover:underline dark:text-cagsu-yellow" @click="goToStep(3)">Edit</button>
                            </div>
                            <dl class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                <div><dt class="text-xs text-gray-500">Password</dt><dd>••••••••</dd></div>
                                <div>
                                    <dt class="text-xs text-gray-500">ID Images</dt>
                                    <dd x-text="idProofNamesLabel()"></dd>
                                </div>
                            </dl>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">File uploads cannot be autosaved by the browser. Reattach your file if needed.</p>
                        </div>
                    </div>
                </section>
            </div>

            <div class="rounded-md border border-cagsu-yellow/60 bg-cagsu-yellow/10 px-4 py-3 text-sm text-gray-700 dark:border-cagsu-yellow/30 dark:text-gray-300">
                <strong class="font-semibold text-cagsu-maroon dark:text-cagsu-yellow">What happens next?</strong>
                Your registration will be reviewed and approved by the CEO before your account is activated. You will be able to log in once approved.
            </div>

            <div class="flex items-center justify-between gap-3">
                <button
                    type="button"
                    class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-900"
                    x-show="step > 1"
                    x-cloak
                    @click="prevStep()"
                >
                    Back
                </button>
                <div class="ml-auto">
                    <button
                        type="button"
                        class="inline-flex items-center rounded-md bg-cagsu-maroon px-4 py-2 text-sm font-semibold text-white transition hover:bg-cagsu-orange dark:bg-cagsu-yellow dark:text-gray-900 dark:hover:bg-cagsu-orange"
                        x-show="step < 4"
                        @click="nextStep()"
                    >
                        Next
                    </button>
                    <x-primary-button class="justify-center" x-show="step === 4" x-cloak>
                        {{ __('Submit Registration') }}
                    </x-primary-button>
                </div>
            </div>

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
    </div>
</x-guest-layout>
