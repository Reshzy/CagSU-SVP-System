<x-guest-layout>
    <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <!-- Department -->
        <div class="mt-4">
            <x-input-label for="department_id" :value="__('Department')" />
            <select id="department_id" name="department_id" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" required>
                <option value="">-- Select department --</option>
                @isset($departments)
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" @selected(old('department_id')==$dept->id)>{{ $dept->name }}</option>
                    @endforeach
                @endisset
            </select>
            <x-input-error :messages="$errors->get('department_id')" class="mt-2" />
        </div>

        <!-- Employee ID -->
        <div class="mt-4">
            <x-input-label for="employee_id" :value="__('Employee ID (optional)')" />
            <x-text-input id="employee_id" class="block mt-1 w-full" type="text" name="employee_id" :value="old('employee_id')" autocomplete="employee-id" />
            <x-input-error :messages="$errors->get('employee_id')" class="mt-2" />
        </div>

        <!-- Position -->
        <div class="mt-4">
            <x-input-label for="position_id" :value="__('Position')" />
            <select id="position_id" name="position_id" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" required>
                <option value="">-- Select position --</option>
                @isset($positions)
                    @foreach($positions as $pos)
                        <option value="{{ $pos->id }}" @selected(old('position_id')==$pos->id)>{{ $pos->name }}</option>
                    @endforeach
                @endisset
            </select>
            <x-input-error :messages="$errors->get('position_id')" class="mt-2" />
        </div>

        <!-- Phone -->
        <div class="mt-4">
            <x-input-label for="phone" :value="__('Phone (optional)')" />
            <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" :value="old('phone')" autocomplete="tel" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <!-- ID Proof Upload -->
        <div class="mt-4">
            <x-input-label for="id_proof" :value="__('Upload University ID or Valid ID (PDF/JPG/PNG/DOC)')" />
            <input id="id_proof" class="block mt-1 w-full" type="file" name="id_proof" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required />
            <x-input-error :messages="$errors->get('id_proof')" class="mt-2" />
            <p class="text-sm text-gray-600 mt-1">Max 10MB. Accepted: PDF, JPG, PNG, DOC, DOCX.</p>
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
