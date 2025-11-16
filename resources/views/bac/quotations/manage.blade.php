@section('title', 'BAC - Manage Quotations')

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('Manage Quotations: ') . $purchaseRequest->pr_number }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-6">
                    @if(session('status'))
                        <div class="mb-4 p-3 rounded-md bg-green-50 text-green-700">{{ session('status') }}</div>
                    @endif

                    @if(session('error'))
                        <div class="mb-4 p-3 rounded-md bg-red-50 text-red-700">{{ session('error') }}</div>
                    @endif

                    {{-- BAC Resolution Section --}}
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="font-semibold text-lg text-gray-800 mb-2">BAC Resolution</h3>
                                @if($resolution && $purchaseRequest->resolution_number)
                                    <div class="space-y-2">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm text-gray-600">Resolution Number:</span>
                                            <span class="font-mono font-semibold text-gray-800">{{ $purchaseRequest->resolution_number }}</span>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm text-gray-600">Earmark ID:</span>
                                            <span class="font-mono text-gray-800">{{ $purchaseRequest->earmark_id ?? 'N/A' }}</span>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm text-gray-600">Generated:</span>
                                            <span class="text-sm text-gray-800">{{ $resolution->created_at->format('M d, Y h:i A') }}</span>
                                        </div>
                                    </div>
                                @else
                                    <p class="text-gray-600">Resolution is being generated or not yet available.</p>
                                @endif
                            </div>
                            <div class="flex flex-col space-y-2 ml-4">
                                @if($resolution && $purchaseRequest->resolution_number)
                                    <a href="{{ route('bac.quotations.resolution.download', $purchaseRequest) }}" 
                                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        Download Resolution
                                    </a>
                                    <button type="button" 
                                            onclick="document.getElementById('regenerateModal').classList.remove('hidden')"
                                            class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 w-full">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                        Regenerate
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- RFQ Section --}}
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="font-semibold text-lg text-gray-800 mb-2">Request for Quotation (RFQ)</h3>
                                @if($rfq && $purchaseRequest->rfq_number)
                                    <div class="space-y-2">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm text-gray-600">RFQ Number:</span>
                                            <span class="font-mono font-semibold text-gray-800">{{ $purchaseRequest->rfq_number }}</span>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm text-gray-600">Generated:</span>
                                            <span class="text-sm text-gray-800">{{ $rfq->created_at->format('M d, Y h:i A') }}</span>
                                        </div>
                                    </div>
                                @else
                                    <p class="text-gray-600">RFQ not yet generated. Generate resolution first, then click Generate RFQ.</p>
                                @endif
                            </div>
                            <div class="flex flex-col space-y-2 ml-4">
                                @if($rfq && $purchaseRequest->rfq_number)
                                    <a href="{{ route('bac.quotations.rfq.download', $purchaseRequest) }}" 
                                       class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        Download RFQ
                                    </a>
                                    <button type="button" 
                                            onclick="document.getElementById('regenerateRfqModal').classList.remove('hidden')"
                                            class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 w-full">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                        Regenerate
                                    </button>
                                @elseif($purchaseRequest->resolution_number)
                                    <form action="{{ route('bac.quotations.rfq.generate', $purchaseRequest) }}" method="POST">
                                        @csrf
                                        <button type="submit" 
                                                class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                            </svg>
                                            Generate RFQ
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <div class="text-sm text-gray-600">Purpose</div>
                            <div class="font-medium">{{ $purchaseRequest->purpose }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600">Procurement Method</div>
                            <div class="font-medium capitalize">{{ str_replace('_', ' ', $purchaseRequest->procurement_method ?? 'N/A') }}</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="font-semibold mb-2">Record Supplier Quotation</h3>
                            <form action="{{ route('bac.quotations.store', $purchaseRequest) }}" method="POST" class="space-y-3">
                                @csrf
                                <div>
                                    <label class="text-sm text-gray-600">Supplier</label>
                                    <select name="supplier_id" class="w-full border-gray-300 rounded-md" required>
                                        @foreach($suppliers as $s)
                                            <option value="{{ $s->id }}">{{ $s->business_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-sm text-gray-600">Quotation Date</label>
                                        <input type="date" name="quotation_date" class="w-full border-gray-300 rounded-md" required />
                                    </div>
                                    <div>
                                        <label class="text-sm text-gray-600">Validity Date</label>
                                        <input type="date" name="validity_date" class="w-full border-gray-300 rounded-md" required />
                                    </div>
                                </div>
                                <div>
                                    <label class="text-sm text-gray-600">Total Amount</label>
                                    <input type="number" step="0.01" name="total_amount" class="w-full border-gray-300 rounded-md" required />
                                </div>
                                <x-primary-button>Save Quotation</x-primary-button>
                            </form>
                        </div>
                        <div>
                            <h3 class="font-semibold mb-2">Submitted Quotations</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th class="px-4 py-2"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @forelse($quotations as $q)
                                        <tr>
                                            <td class="px-4 py-2">{{ $q->supplier?->business_name }}</td>
                                            <td class="px-4 py-2">{{ number_format((float)$q->total_amount, 2) }}</td>
                                            <td class="px-4 py-2 capitalize">{{ str_replace('_', ' ', $q->bac_status) }}</td>
                                            <td class="px-4 py-2 text-right">
                                                <form action="{{ route('bac.quotations.evaluate', $q) }}" method="POST" class="inline-flex items-center space-x-2">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="number" step="0.01" name="technical_score" placeholder="Tech" class="w-20 border-gray-300 rounded-md" />
                                                    <input type="number" step="0.01" name="financial_score" placeholder="Fin" class="w-20 border-gray-300 rounded-md" />
                                                    <select name="bac_status" class="border-gray-300 rounded-md">
                                                        <option value="compliant">Compliant</option>
                                                        <option value="non_compliant">Non-compliant</option>
                                                        <option value="lowest_bidder">Lowest Bidder</option>
                                                    </select>
                                                    <input type="text" name="bac_remarks" placeholder="Remarks" class="border-gray-300 rounded-md" />
                                                    <x-primary-button>Save</x-primary-button>
                                                </form>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="4" class="px-4 py-6 text-center text-gray-500">No quotations yet.</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('bac.quotations.finalize', $purchaseRequest) }}" method="POST" class="mt-6 border-t pt-4">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                            <div>
                                <label class="text-sm text-gray-600">Winning Quotation</label>
                                <select name="winning_quotation_id" class="w-full border-gray-300 rounded-md">
                                    <option value="">Select winner (optional)</option>
                                    @foreach($quotations as $q)
                                        <option value="{{ $q->id }}">{{ $q->supplier?->business_name }} - ₱{{ number_format((float)$q->total_amount, 2) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-2 text-right">
                                <x-primary-button>Finalize Abstract</x-primary-button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Regenerate Resolution Modal -->
    <div id="regenerateModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-10 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Regenerate Resolution</h3>
                <button onclick="document.getElementById('regenerateModal').classList.add('hidden')" 
                        class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form action="{{ route('bac.quotations.resolution.regenerate', $purchaseRequest) }}" method="POST">
                @csrf
                
                <div class="mb-4">
                    <p class="text-sm text-gray-600">Update the signatory information and regenerate the BAC resolution document. Leave fields unchanged if you want to keep existing signatories.</p>
                </div>

                <!-- Signatory Selection Form -->
                @include('bac.partials.signatory_form', [
                    'signatories' => $purchaseRequest->resolutionSignatories ?? null,
                    'bacSignatories' => $bacSignatories ?? []
                ])

                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" 
                            onclick="document.getElementById('regenerateModal').classList.add('hidden')"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                        Regenerate Resolution
                    </button>
                </div>
            </form>
        </div>
    </div>

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
                                           class="form-radio text-cagsu-blue" 
                                           onchange="toggleRfqInputMode('{{ $position }}', 'select')">
                                    <span class="ml-2 text-sm">Select from list</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="signatories[{{ $position }}][input_mode]" value="manual" 
                                           {{ $inputMode == 'manual' ? 'checked' : '' }}
                                           class="form-radio text-cagsu-blue" 
                                           onchange="toggleRfqInputMode('{{ $position }}', 'manual')">
                                    <span class="ml-2 text-sm">Manual entry</span>
                                </label>
                            </div>

                            <!-- Select from List -->
                            <div id="rfq-{{ $position }}-select-section" class="{{ $inputMode == 'select' ? '' : 'hidden' }}">
                                <select name="signatories[{{ $position }}][user_id]" 
                                        id="rfq-{{ $position }}-select-dropdown"
                                        class="block w-full border-gray-300 rounded-md shadow-sm focus:border-cagsu-blue focus:ring focus:ring-cagsu-blue focus:ring-opacity-50 mb-2"
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
                                       class="block w-full border-gray-300 rounded-md shadow-sm focus:border-cagsu-blue focus:ring focus:ring-cagsu-blue focus:ring-opacity-50 mb-2">
                            </div>

                            <!-- Prefix and Suffix -->
                            <div class="grid grid-cols-2 gap-3 mt-3">
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Prefix (Optional)</label>
                                    <select name="signatories[{{ $position }}][prefix]" 
                                            id="rfq-{{ $position }}-prefix-field"
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
                                           id="rfq-{{ $position }}-suffix-field"
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
</x-app-layout>


