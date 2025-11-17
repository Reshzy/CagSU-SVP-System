<!-- Regenerate RFQ Modal -->
<div id="regenerateRfqModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-11/12 max-w-3xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Regenerate RFQ</h3>
            <button onclick="document.getElementById('regenerateRfqModal').classList.add('hidden')" 
                    class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form action="{{ route('bac.quotations.rfq.regenerate', $purchaseRequest) }}" method="POST">
            @csrf
            
            <div class="mb-4">
                <p class="text-sm text-gray-600">Update the signatory information and regenerate the RFQ document.</p>
            </div>

            @php
                $existingRfqSignatories = $purchaseRequest->rfqSignatories ?? collect();
                $rfqPositions = [
                    'bac_chairperson' => 'BAC Chairperson',
                    'canvassing_officer' => 'Canvassing Officer',
                ];
                $prefixes = ['', 'Dr.', 'Atty.', 'Engr.', 'Prof.', 'Mr.', 'Ms.', 'Mrs.'];
                
                // Get BAC users for dropdown, including Canvassing Officer
                $bacUsers = \App\Models\User::whereHas('roles', function ($query) {
                    $query->whereIn('name', ['BAC Chair', 'BAC Members', 'BAC Secretariat', 'Executive Officer', 'System Admin', 'Canvassing Unit']);
                })->orderBy('name')->get();
                
                // If no users found with roles, get all users as fallback
                if ($bacUsers->isEmpty()) {
                    $bacUsers = \App\Models\User::orderBy('name')->get();
                }
            @endphp

            <!-- RFQ Signatory Selection Form -->
            <div class="space-y-6 bg-gray-50 p-6 rounded-lg">
                @foreach($rfqPositions as $position => $label)
                    @php
                        // Map RFQ position to BAC signatory position
                        // RFQ uses 'bac_chairperson' but BAC signatories use 'bac_chairman'
                        $bacPosition = $position === 'bac_chairperson' ? 'bac_chairman' : $position;
                        $bacSigs = $bacSignatories->get($bacPosition, collect());
                        $existingSig = $existingRfqSignatories->firstWhere('position', $position);
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
                                       class="form-radio text-blue-600" 
                                       onchange="toggleRfqInputMode('{{ $position }}', 'select')">
                                <span class="ml-2 text-sm">Select from list</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="signatories[{{ $position }}][input_mode]" value="manual" 
                                       {{ $inputMode == 'manual' ? 'checked' : '' }}
                                       class="form-radio text-blue-600" 
                                       onchange="toggleRfqInputMode('{{ $position }}', 'manual')">
                                <span class="ml-2 text-sm">Manual entry</span>
                            </label>
                        </div>

                        <!-- Select from List -->
                        <div id="rfq-{{ $position }}-select-section" class="{{ $inputMode == 'select' ? '' : 'hidden' }}">
                            <select name="signatories[{{ $position }}][user_id]" 
                                    id="rfq-{{ $position }}-select-dropdown"
                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 mb-2"
                                    onchange="handleRfqSignatorySelection('{{ $position }}')">
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
                            <input type="hidden" name="signatories[{{ $position }}][selected_name]" id="rfq-{{ $position }}-selected-name" value="">
                        </div>

                        <!-- Manual Entry -->
                        <div id="rfq-{{ $position }}-manual-section" class="{{ $inputMode == 'manual' ? '' : 'hidden' }}">
                            <input type="text" name="signatories[{{ $position }}][name]" 
                                   value="{{ old("signatories.{$position}.name", $existingSig->name ?? '') }}"
                                   placeholder="Enter full name" 
                                   class="block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 mb-2">
                        </div>

                        <!-- Prefix and Suffix -->
                        <div class="grid grid-cols-2 gap-3 mt-3">
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Prefix (Optional)</label>
                                <select name="signatories[{{ $position }}][prefix]" 
                                        id="rfq-{{ $position }}-prefix-field"
                                        class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
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
                                       id="rfq-{{ $position }}-suffix-field"
                                       value="{{ old("signatories.{$position}.suffix", $existingSig->suffix ?? '') }}"
                                       placeholder="e.g., Ph.D., M.A., CPA" 
                                       class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                            </div>
                        </div>

                        @error("signatories.{$position}.*")
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach
            </div>

            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" 
                        onclick="document.getElementById('regenerateRfqModal').classList.add('hidden')"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                    Regenerate RFQ
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleRfqInputMode(position, mode) {
    const selectSection = document.getElementById('rfq-' + position + '-select-section');
    const manualSection = document.getElementById('rfq-' + position + '-manual-section');
    const prefixField = document.getElementById('rfq-' + position + '-prefix-field');
    const suffixField = document.getElementById('rfq-' + position + '-suffix-field');
    
    if (mode === 'select') {
        selectSection.classList.remove('hidden');
        manualSection.classList.add('hidden');
        // Clear manual input when switching to select
        manualSection.querySelector('input').value = '';
        // Trigger selection handler to populate hidden fields and handle disabled state
        handleRfqSignatorySelection(position);
    } else {
        selectSection.classList.add('hidden');
        manualSection.classList.remove('hidden');
        // Clear select when switching to manual
        selectSection.querySelector('select').value = '';
        // Clear hidden selected name field
        document.getElementById('rfq-' + position + '-selected-name').value = '';
        
        // Enable prefix and suffix fields for manual entry
        prefixField.style.pointerEvents = '';
        suffixField.readOnly = false;
        prefixField.classList.remove('bg-gray-100', 'cursor-not-allowed');
        suffixField.classList.remove('bg-gray-100', 'cursor-not-allowed');
    }
}

function handleRfqSignatorySelection(position) {
    const dropdown = document.getElementById('rfq-' + position + '-select-dropdown');
    const selectedOption = dropdown.options[dropdown.selectedIndex];
    const hiddenNameField = document.getElementById('rfq-' + position + '-selected-name');
    const prefixField = document.getElementById('rfq-' + position + '-prefix-field');
    const suffixField = document.getElementById('rfq-' + position + '-suffix-field');
    
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
        
        // If this is a pre-configured signatory
        if (isPreConfigured) {
            // Make fields readonly (pre-configured values)
            prefixField.style.pointerEvents = 'none';
            prefixField.classList.add('bg-gray-100', 'cursor-not-allowed');
            
            suffixField.readOnly = true;
            suffixField.classList.add('bg-gray-100', 'cursor-not-allowed');
            
            // Set the pre-configured values
            prefixField.value = prefix;
            suffixField.value = suffix;
            
            // If this is a pre-configured signatory with manual name (no user account)
            if (isManual && manualName) {
                hiddenNameField.value = manualName;
            }
        } else {
            // Enable prefix and suffix fields (user can customize)
            prefixField.style.pointerEvents = '';
            suffixField.readOnly = false;
            prefixField.classList.remove('bg-gray-100', 'cursor-not-allowed');
            suffixField.classList.remove('bg-gray-100', 'cursor-not-allowed');
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
    const positions = ['bac_chairperson', 'canvassing_officer'];
    positions.forEach(function(position) {
        const dropdown = document.getElementById('rfq-' + position + '-select-dropdown');
        if (dropdown && dropdown.selectedIndex > 0) {
            handleRfqSignatorySelection(position);
        }
    });
});
</script>

