@section('title', 'Add BAC Signatory')

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('Add BAC Signatory') }}</h2>
            <a href="{{ route('bac.signatories.index') }}" class="text-cagsu-blue hover:underline">
                ← Back to List
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if (session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form action="{{ route('bac.signatories.store') }}" method="POST" class="space-y-6">
                        @csrf

                        <!-- Input Type Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Entry Type <span class="text-red-500">*</span></label>
                            <div class="flex space-x-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" name="input_type" value="user" 
                                           {{ old('input_type', 'user') == 'user' ? 'checked' : '' }}
                                           class="form-radio text-cagsu-blue" 
                                           onchange="toggleInputType('user')">
                                    <span class="ml-2">Select from User Accounts</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="input_type" value="manual" 
                                           {{ old('input_type') == 'manual' ? 'checked' : '' }}
                                           class="form-radio text-cagsu-blue" 
                                           onchange="toggleInputType('manual')">
                                    <span class="ml-2">Manual Entry</span>
                                </label>
                            </div>
                        </div>

                        <!-- User Selection -->
                        <div id="user-section" class="{{ old('input_type', 'user') == 'user' ? '' : 'hidden' }}">
                            <label for="user_id" class="block text-sm font-medium text-gray-700">User <span class="text-red-500">*</span></label>
                            <select name="user_id" id="user_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-cagsu-blue focus:ring focus:ring-cagsu-blue focus:ring-opacity-50">
                                <option value="">Select a user</option>
                                @foreach($bacUsers as $user)
                                    <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->getRoleNames()->implode(', ') }})
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Manual Name Entry -->
                        <div id="manual-section" class="{{ old('input_type') == 'manual' ? '' : 'hidden' }}">
                            <label for="manual_name" class="block text-sm font-medium text-gray-700">Full Name <span class="text-red-500">*</span></label>
                            <input type="text" name="manual_name" id="manual_name" value="{{ old('manual_name') }}" 
                                   placeholder="Enter full name" 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-cagsu-blue focus:ring focus:ring-cagsu-blue focus:ring-opacity-50">
                            <p class="mt-1 text-xs text-gray-500">For BAC members who don't have user accounts yet</p>
                            @error('manual_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="position" class="block text-sm font-medium text-gray-700">Position <span class="text-red-500">*</span></label>
                            <select name="position" id="position" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-cagsu-blue focus:ring focus:ring-cagsu-blue focus:ring-opacity-50" required>
                                <option value="">Select a position</option>
                                <option value="bac_chairman" {{ old('position') == 'bac_chairman' ? 'selected' : '' }}>BAC Chairman</option>
                                <option value="bac_vice_chairman" {{ old('position') == 'bac_vice_chairman' ? 'selected' : '' }}>BAC Vice Chairman</option>
                                <option value="bac_member" {{ old('position') == 'bac_member' ? 'selected' : '' }}>BAC Member</option>
                                <option value="head_bac_secretariat" {{ old('position') == 'head_bac_secretariat' ? 'selected' : '' }}>Head, BAC Secretariat</option>
                                <option value="ceo" {{ old('position') == 'ceo' ? 'selected' : '' }}>CEO</option>
                                <option value="canvassing_officer" {{ old('position') == 'canvassing_officer' ? 'selected' : '' }}>Canvassing Officer</option>
                            </select>
                            @error('position')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="prefix" class="block text-sm font-medium text-gray-700">Prefix (Optional)</label>
                                <input type="text" name="prefix" id="prefix" value="{{ old('prefix') }}" placeholder="e.g., Dr., Atty., Engr." class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-cagsu-blue focus:ring focus:ring-cagsu-blue focus:ring-opacity-50">
                                <p class="mt-1 text-xs text-gray-500">Examples: Dr., Atty., Engr., Prof.</p>
                                @error('prefix')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="suffix" class="block text-sm font-medium text-gray-700">Suffix (Optional)</label>
                                <input type="text" name="suffix" id="suffix" value="{{ old('suffix') }}" placeholder="e.g., Ph.D., M.A., CPA" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-cagsu-blue focus:ring focus:ring-cagsu-blue focus:ring-opacity-50">
                                <p class="mt-1 text-xs text-gray-500">Examples: Ph.D., M.A., CPA, MBA</p>
                                @error('suffix')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="rounded border-gray-300 text-cagsu-blue shadow-sm focus:border-cagsu-blue focus:ring focus:ring-cagsu-blue focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700">Active (Available for selection in resolutions)</span>
                            </label>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('bac.signatories.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded-lg">
                                Cancel
                            </a>
                            <button type="submit" class="bg-cagsu-blue hover:bg-blue-800 text-white font-semibold py-2 px-4 rounded-lg shadow">
                                Add Signatory
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleInputType(type) {
            const userSection = document.getElementById('user-section');
            const manualSection = document.getElementById('manual-section');
            
            if (type === 'user') {
                userSection.classList.remove('hidden');
                manualSection.classList.add('hidden');
                document.getElementById('manual_name').value = '';
            } else {
                userSection.classList.add('hidden');
                manualSection.classList.remove('hidden');
                document.getElementById('user_id').value = '';
            }
        }
    </script>
</x-app-layout>

