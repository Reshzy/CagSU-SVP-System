{{-- 
    Signatory Selection Form Component
    This component allows selecting signatories for BAC resolutions
    Can be used in both initial generation and regeneration forms
--}}

@props(['signatories' => null, 'bacSignatories' => []])

@php
    // Load BAC signatories if not provided
    if (empty($bacSignatories)) {
        $bacSignatories = \App\Models\BacSignatory::with('user')->active()->get()->groupBy('position');
    }
    
    // Load existing signatories if available
    $existing = $signatories ?? [];
    
    // Get BAC users for manual entry dropdown
    $bacUsers = \App\Models\User::whereHas('roles', function ($query) {
        $query->whereIn('name', ['BAC Chair', 'BAC Members', 'BAC Secretariat', 'Executive Officer', 'System Admin']);
    })->orderBy('name')->get();
    
    // Position definitions
    $positions = [
        'bac_chairman' => 'BAC Chairman',
        'bac_vice_chairman' => 'BAC Vice Chairman',
        'bac_member_1' => 'BAC Member 1',
        'bac_member_2' => 'BAC Member 2',
        'bac_member_3' => 'BAC Member 3',
        'head_bac_secretariat' => 'Head, BAC Secretariat',
        'ceo' => 'CEO',
    ];
    
    // Common title prefixes and suffixes
    $prefixes = ['', 'Dr.', 'Atty.', 'Engr.', 'Prof.', 'Mr.', 'Ms.', 'Mrs.'];
@endphp

<div class="space-y-6 bg-gray-50 p-6 rounded-lg">
    <div class="mb-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Resolution Signatories</h3>
        <p class="text-sm text-gray-600">Select or enter names for each required signatory position. You can choose from pre-configured signatories or manually enter names.</p>
    </div>

    @foreach($positions as $position => $label)
        @php
            $positionKey = str_replace(['_1', '_2', '_3'], '', $position);
            $bacSigs = $bacSignatories[$positionKey] ?? collect();
            $existingSig = collect($existing)->firstWhere('position', $position);
            $inputMode = old("signatories.{$position}.input_mode", $existingSig ? 'select' : 'select');
        @endphp
        
        <div class="bg-white p-4 rounded border border-gray-200">
            <div class="mb-3">
                <label class="block text-sm font-semibold text-gray-700 mb-2">{{ $label }} <span class="text-red-500">*</span></label>
            </div>

            <!-- Input Mode Selection -->
            <div class="mb-3 flex space-x-4">
                <label class="inline-flex items-center">
                    <input type="radio" name="signatories[{{ $position }}][input_mode]" value="select" 
                           {{ $inputMode == 'select' ? 'checked' : '' }}
                           class="form-radio text-cagsu-blue" 
                           onchange="toggleInputMode('{{ $position }}', 'select')">
                    <span class="ml-2 text-sm">Select from list</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="radio" name="signatories[{{ $position }}][input_mode]" value="manual" 
                           {{ $inputMode == 'manual' ? 'checked' : '' }}
                           class="form-radio text-cagsu-blue" 
                           onchange="toggleInputMode('{{ $position }}', 'manual')">
                    <span class="ml-2 text-sm">Manual entry</span>
                </label>
            </div>

            <!-- Select from List -->
            <div id="{{ $position }}-select-section" class="{{ $inputMode == 'select' ? '' : 'hidden' }}">
                <select name="signatories[{{ $position }}][user_id]" 
                        id="{{ $position }}-select-dropdown"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:border-cagsu-blue focus:ring focus:ring-cagsu-blue focus:ring-opacity-50 mb-2"
                        onchange="handleSignatorySelection('{{ $position }}')">
                    <option value="">-- Select a signatory --</option>
                    @if($bacSigs->isNotEmpty())
                        <optgroup label="Pre-configured Signatories">
                            @foreach($bacSigs as $bacSig)
                                <option value="{{ $bacSig->user_id ?? '' }}" 
                                        data-signatory-id="{{ $bacSig->id }}"
                                        data-prefix="{{ $bacSig->prefix ?? '' }}" 
                                        data-suffix="{{ $bacSig->suffix ?? '' }}"
                                        data-manual-name="{{ $bacSig->manual_name ?? '' }}"
                                        data-display-name="{{ $bacSig->display_name }}"
                                        data-is-manual="{{ $bacSig->user_id ? 'false' : 'true' }}"
                                        {{ old("signatories.{$position}.user_id", $existingSig->user_id ?? '') == $bacSig->user_id ? 'selected' : '' }}>
                                    {{ $bacSig->full_name }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endif
                    <optgroup label="All BAC Users">
                        @foreach($bacUsers as $user)
                            <option value="{{ $user->id }}" 
                                    data-is-manual="false"
                                    {{ old("signatories.{$position}.user_id", $existingSig->user_id ?? '') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->getRoleNames()->implode(', ') }})
                            </option>
                        @endforeach
                    </optgroup>
                </select>
                <!-- Hidden input for manual names from pre-configured signatories -->
                <input type="hidden" name="signatories[{{ $position }}][selected_name]" id="{{ $position }}-selected-name" value="">
            </div>

            <!-- Manual Entry -->
            <div id="{{ $position }}-manual-section" class="{{ $inputMode == 'manual' ? '' : 'hidden' }}">
                <input type="text" name="signatories[{{ $position }}][name]" 
                       value="{{ old("signatories.{$position}.name", $existingSig->name ?? '') }}"
                       placeholder="Enter full name" 
                       class="block w-full border-gray-300 rounded-md shadow-sm focus:border-cagsu-blue focus:ring focus:ring-cagsu-blue focus:ring-opacity-50 mb-2">
            </div>

            <!-- Prefix and Suffix -->
            <div class="grid grid-cols-2 gap-3 mt-3">
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Prefix (Optional)</label>
                    <select name="signatories[{{ $position }}][prefix]" 
                            id="{{ $position }}-prefix-field"
                            class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-cagsu-blue focus:ring focus:ring-cagsu-blue focus:ring-opacity-50">
                        @foreach($prefixes as $prefix)
                            <option value="{{ $prefix }}" {{ old("signatories.{$position}.prefix", $existingSig->prefix ?? '') == $prefix ? 'selected' : '' }}>
                                {{ $prefix ?: '-- None --' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Suffix (Optional)</label>
                    <input type="text" name="signatories[{{ $position }}][suffix]" 
                           id="{{ $position }}-suffix-field"
                           value="{{ old("signatories.{$position}.suffix", $existingSig->suffix ?? '') }}"
                           placeholder="e.g., Ph.D., M.A., CPA" 
                           class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-cagsu-blue focus:ring focus:ring-cagsu-blue focus:ring-opacity-50">
                </div>
            </div>

            @error("signatories.{$position}.*")
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    @endforeach
</div>

<script>
function toggleInputMode(position, mode) {
    const selectSection = document.getElementById(position + '-select-section');
    const manualSection = document.getElementById(position + '-manual-section');
    const prefixField = document.getElementById(position + '-prefix-field');
    const suffixField = document.getElementById(position + '-suffix-field');
    
    if (mode === 'select') {
        selectSection.classList.remove('hidden');
        manualSection.classList.add('hidden');
        // Clear manual input when switching to select
        manualSection.querySelector('input').value = '';
        // Trigger selection handler to populate hidden fields and handle disabled state
        handleSignatorySelection(position);
    } else {
        selectSection.classList.add('hidden');
        manualSection.classList.remove('hidden');
        // Clear select when switching to manual
        selectSection.querySelector('select').value = '';
        // Clear hidden selected name field
        document.getElementById(position + '-selected-name').value = '';
        
        // Enable prefix and suffix fields for manual entry
        prefixField.style.pointerEvents = '';
        suffixField.readOnly = false;
        prefixField.classList.remove('bg-gray-100', 'cursor-not-allowed');
        suffixField.classList.remove('bg-gray-100', 'cursor-not-allowed');
    }
}

function handleSignatorySelection(position) {
    const dropdown = document.getElementById(position + '-select-dropdown');
    const selectedOption = dropdown.options[dropdown.selectedIndex];
    const hiddenNameField = document.getElementById(position + '-selected-name');
    const prefixField = document.getElementById(position + '-prefix-field');
    const suffixField = document.getElementById(position + '-suffix-field');
    
    // Reset hidden field
    hiddenNameField.value = '';
    
    // Check if a valid option is selected (not the placeholder)
    if (selectedOption && dropdown.selectedIndex > 0) {
        const signatoryId = selectedOption.getAttribute('data-signatory-id');
        const isManual = selectedOption.getAttribute('data-is-manual') === 'true';
        const manualName = selectedOption.getAttribute('data-manual-name') || '';
        const displayName = selectedOption.getAttribute('data-display-name') || '';
        const prefix = selectedOption.getAttribute('data-prefix') || '';
        const suffix = selectedOption.getAttribute('data-suffix') || '';
        const userId = selectedOption.value;
        const isPreConfigured = signatoryId !== null && signatoryId !== '';
        
        console.log('Selected signatory for ' + position + ':', {
            isPreConfigured: isPreConfigured,
            isManual: isManual,
            manualName: manualName,
            displayName: displayName,
            userId: userId,
            prefix: prefix,
            suffix: suffix
        });
        
        // If this is a pre-configured signatory
        if (isPreConfigured) {
            // Make fields readonly (pre-configured values) - use pointer-events to prevent interaction
            // Note: We can't use disabled because disabled fields don't submit with the form
            prefixField.style.pointerEvents = 'none';
            prefixField.classList.add('bg-gray-100', 'cursor-not-allowed');
            
            suffixField.readOnly = true;
            suffixField.classList.add('bg-gray-100', 'cursor-not-allowed');
            
            // Set the pre-configured values (or clear if empty)
            prefixField.value = prefix;
            suffixField.value = suffix;
            
            // If this is a pre-configured signatory with manual name (no user account)
            if (isManual && manualName) {
                hiddenNameField.value = manualName;
                console.log('✓ Set hidden name field for ' + position + ' to: "' + manualName + '"');
            }
        } else {
            // Enable prefix and suffix fields (user can customize)
            prefixField.style.pointerEvents = '';
            suffixField.readOnly = false;
            prefixField.classList.remove('bg-gray-100', 'cursor-not-allowed');
            suffixField.classList.remove('bg-gray-100', 'cursor-not-allowed');
            
            // Don't auto-fill for non-pre-configured users
            // User can manually set if needed
        }
    } else {
        // No selection - enable fields and clear values
        prefixField.style.pointerEvents = '';
        suffixField.readOnly = false;
        prefixField.classList.remove('bg-gray-100', 'cursor-not-allowed');
        suffixField.classList.remove('bg-gray-100', 'cursor-not-allowed');
        prefixField.value = '';
        suffixField.value = '';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing signatory form...');
    // Initialize all position dropdowns
    const positions = ['bac_chairman', 'bac_vice_chairman', 'bac_member_1', 'bac_member_2', 'bac_member_3', 'head_bac_secretariat', 'ceo'];
    positions.forEach(function(position) {
        const dropdown = document.getElementById(position + '-select-dropdown');
        if (dropdown && dropdown.selectedIndex > 0) {
            handleSignatorySelection(position);
        }
    });
});
</script>


