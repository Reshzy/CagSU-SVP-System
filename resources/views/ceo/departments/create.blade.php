<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">New Department</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <a href="{{ route('ceo.departments.index') }}" class="text-sm underline">Back to list</a>
                    <form method="POST" action="{{ route('ceo.departments.store') }}" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium">Name</label>
                            <input type="text" name="name" value="{{ old('name') }}" class="mt-1 block w-full border-gray-300 rounded-md" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-1" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Code</label>
                            <input type="text" name="code" value="{{ old('code') }}" class="mt-1 block w-full border-gray-300 rounded-md" maxlength="10" required />
                            <x-input-error :messages="$errors->get('code')" class="mt-1" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Description</label>
                            <textarea name="description" class="mt-1 block w-full border-gray-300 rounded-md" rows="3">{{ old('description') }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-1" />
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium">Head Name</label>
                                <input type="text" name="head_name" value="{{ old('head_name') }}" class="mt-1 block w-full border-gray-300 rounded-md" />
                                <x-input-error :messages="$errors->get('head_name')" class="mt-1" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Contact Email</label>
                                <input type="email" name="contact_email" value="{{ old('contact_email') }}" class="mt-1 block w-full border-gray-300 rounded-md" />
                                <x-input-error :messages="$errors->get('contact_email')" class="mt-1" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Contact Phone</label>
                            <input type="text" name="contact_phone" value="{{ old('contact_phone') }}" class="mt-1 block w-full border-gray-300 rounded-md" />
                            <x-input-error :messages="$errors->get('contact_phone')" class="mt-1" />
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="is_active" name="is_active" value="1" class="rounded" checked />
                            <label for="is_active" class="text-sm">Active</label>
                        </div>
                        <div class="pt-2">
                            <button class="px-4 py-2 border rounded">Create</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>


