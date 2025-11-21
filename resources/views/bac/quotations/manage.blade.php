@section('title', 'BAC - Manage Quotations')

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('Manage Quotations: ') . $purchaseRequest->pr_number }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-6">
                    @if(session('status'))
                        <div class="mb-4 p-3 rounded-md bg-green-50 text-green-700">{{ session('status') }}</div>
                    @endif

                    @if(session('error'))
                        <div class="mb-4 p-3 rounded-md bg-red-50 text-red-700">{{ session('error') }}</div>
                    @endif

                    {{-- BAC Resolution Section - UNCHANGED --}}
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

                    {{-- RFQ Section - UNCHANGED --}}
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
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm text-gray-600">Submission Deadline:</span>
                                            <span class="text-sm font-semibold text-gray-800">{{ $rfq->created_at->addDays(4)->format('M d, Y') }}</span>
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

                    {{-- AOQ Section --}}
                    <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 mb-6">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="font-semibold text-lg text-gray-800 mb-2">Abstract of Quotations (AOQ)</h3>
                                <p class="text-sm text-gray-600 mb-2">
                                    Review quotations, resolve ties, and generate the official Abstract of Quotations document.
                                </p>
                            </div>
                            <div class="flex flex-col space-y-2 ml-4">
                                <a href="{{ route('bac.quotations.aoq', $purchaseRequest) }}" 
                                   class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    View / Generate AOQ
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- PR Information Header --}}
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-6">
                        <h3 class="font-semibold text-xl text-gray-800 mb-4">Purchase Request Information</h3>
                        
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <div class="text-sm text-gray-600">PR Number</div>
                                <div class="font-mono font-semibold text-lg">{{ $purchaseRequest->pr_number }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600">Procurement Method</div>
                                <div class="font-medium capitalize">{{ str_replace('_', ' ', $purchaseRequest->procurement_method ?? 'N/A') }}</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="text-sm text-gray-600">Purpose / Justification</div>
                            <div class="font-medium">{{ $purchaseRequest->purpose }}</div>
                        </div>

                        <div class="mt-4">
                            <h4 class="font-semibold text-gray-800 mb-2">PR Items</h4>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 border">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Qty</th>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Unit</th>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Description</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-700 uppercase tracking-wider">ABC (Unit)</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-700 uppercase tracking-wider">ABC (Total)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($purchaseRequest->items as $item)
                                        <tr>
                                            <td class="px-4 py-2 text-sm">{{ $item->quantity_requested }}</td>
                                            <td class="px-4 py-2 text-sm">{{ $item->unit_of_measure }}</td>
                                            <td class="px-4 py-2 text-sm">{{ $item->item_name }}</td>
                                            <td class="px-4 py-2 text-sm text-right font-mono">₱{{ number_format((float)$item->estimated_unit_cost, 2) }}</td>
                                            <td class="px-4 py-2 text-sm text-right font-mono font-semibold">₱{{ number_format((float)$item->estimated_total_cost, 2) }}</td>
                                        </tr>
                                        @endforeach
                                        <tr class="bg-gray-50 font-semibold">
                                            <td colspan="4" class="px-4 py-2 text-sm text-right">Total ABC:</td>
                                            <td class="px-4 py-2 text-sm text-right font-mono text-lg">₱{{ number_format((float)$purchaseRequest->estimated_total, 2) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Record Supplier Quotation Form --}}
                    <div class="bg-white border border-gray-300 rounded-lg p-6 mb-6">
                        <h3 class="font-semibold text-xl text-gray-800 mb-4">Record Supplier Quotation</h3>
                        
                        @if($quotations->count() >= 3)
                            <div class="mb-4 p-3 rounded-md bg-green-50 text-green-700 text-sm">
                                ✓ Minimum requirement met: {{ $quotations->count() }} quotations submitted
                            </div>
                        @else
                            <div class="mb-4 p-3 rounded-md bg-yellow-50 text-yellow-700 text-sm">
                                ⚠ At least 3 quotations required. Current: {{ $quotations->count() }}
                            </div>
                        @endif

                        <form action="{{ route('bac.quotations.store', $purchaseRequest) }}" method="POST" enctype="multipart/form-data" id="quotationForm">
                            @csrf
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Supplier Name <span class="text-red-500">*</span></label>
                                    <select name="supplier_id" id="supplier_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                                        <option value="">-- Select Supplier --</option>
                                        @foreach($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}" 
                                                    data-location="{{ $supplier->address }}, {{ $supplier->city }}, {{ $supplier->province }}"
                                                    {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                                {{ $supplier->business_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('supplier_id')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Supplier Location</label>
                                    <input type="text" name="supplier_location" id="supplier_location" 
                                           value="{{ old('supplier_location') }}"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" 
                                           placeholder="Auto-filled from supplier record">
                                    @error('supplier_location')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Quotation Date <span class="text-red-500">*</span></label>
                                    <input type="date" name="quotation_date" id="quotation_date" 
                                           value="{{ old('quotation_date') }}"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                                    @if($rfq)
                                        <p class="mt-1 text-xs text-gray-500">Deadline: {{ $rfq->created_at->addDays(4)->format('M d, Y') }}</p>
                                    @endif
                                    @error('quotation_date')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Price Validity (Auto-calculated)</label>
                                    <input type="text" id="validity_date_display" 
                                           class="w-full border-gray-300 rounded-md shadow-sm bg-gray-100" 
                                           placeholder="Quotation Date + 10 days" readonly>
                                    <p class="mt-1 text-xs text-gray-500">10 days from quotation date</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Upload Quotation Document</label>
                                    <input type="file" name="quotation_file" id="quotation_file" 
                                           accept=".pdf,.jpg,.jpeg,.png"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <p class="mt-1 text-xs text-gray-500">PDF, JPG, PNG (max 5MB)</p>
                                    @error('quotation_file')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="mt-6">
                                <h4 class="font-semibold text-gray-800 mb-3">Item Pricing</h4>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 border">
                                        <thead class="bg-gray-100">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700 uppercase">Qty</th>
                                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700 uppercase">Unit</th>
                                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700 uppercase">Description</th>
                                                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-700 uppercase">ABC (Unit)</th>
                                                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-700 uppercase">Unit Price</th>
                                                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-700 uppercase">Total Price</th>
                                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-700 uppercase">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200" id="quotationItemsTable">
                                            @foreach($purchaseRequest->items as $index => $item)
                                            <tr class="quotation-item-row">
                                                <td class="px-4 py-2 text-sm">{{ $item->quantity_requested }}</td>
                                                <td class="px-4 py-2 text-sm">{{ $item->unit_of_measure }}</td>
                                                <td class="px-4 py-2 text-sm">{{ $item->item_name }}</td>
                                                <td class="px-4 py-2 text-sm text-right font-mono">₱{{ number_format((float)$item->estimated_unit_cost, 2) }}</td>
                                                <td class="px-4 py-2">
                                                    <input type="hidden" name="items[{{ $index }}][pr_item_id]" value="{{ $item->id }}">
                                                    <input type="number" 
                                                           name="items[{{ $index }}][unit_price]" 
                                                           class="unit-price-input w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-right" 
                                                           data-qty="{{ $item->quantity_requested }}"
                                                           data-abc="{{ $item->estimated_unit_cost }}"
                                                           data-index="{{ $index }}"
                                                           step="0.01" 
                                                           min="0"
                                                           value="{{ old('items.'.$index.'.unit_price') }}"
                                                           placeholder="Leave blank if not quoted">
                                                </td>
                                                <td class="px-4 py-2 text-sm text-right font-mono font-semibold">
                                                    <span class="total-price" id="total_{{ $index }}">₱0.00</span>
                                                </td>
                                                <td class="px-4 py-2 text-center">
                                                    <span class="abc-status" id="status_{{ $index }}"></span>
                                                </td>
                                            </tr>
                                            @endforeach
                                            <tr class="bg-gray-50 font-semibold">
                                                <td colspan="5" class="px-4 py-3 text-right text-base">Grand Total:</td>
                                                <td class="px-4 py-3 text-right text-lg font-mono">
                                                    <span id="grand_total">₱0.00</span>
                                                </td>
                                                <td></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end">
                                <button type="submit" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white text-base font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Save Quotation
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Submitted Quotations --}}
                    <div class="bg-white border border-gray-300 rounded-lg p-6 mb-6">
                        <h3 class="font-semibold text-xl text-gray-800 mb-4">Submitted Quotations</h3>
                        
                        @if($quotations->isEmpty())
                            <div class="text-center py-8 text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p class="mt-2">No quotations submitted yet</p>
                            </div>
                        @else
                            <div class="space-y-4">
                                @foreach($quotations as $quotation)
                                <div class="border border-gray-200 rounded-lg overflow-hidden">
                                    <div class="bg-gray-50 px-4 py-3 flex items-center justify-between cursor-pointer" onclick="toggleQuotationDetails({{ $quotation->id }})">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-3">
                                                <h4 class="font-semibold text-lg">{{ $quotation->supplier->business_name ?? 'N/A' }}</h4>
                                                
                                                @if($quotation->bac_status === 'lowest_bidder')
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        ⭐ Lowest Bidder
                                                    </span>
                                                @elseif($quotation->exceeds_abc)
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        ⚠ Exceeds ABC
                                                    </span>
                                                @elseif($quotation->isValidityExpired())
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                        Expired
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        Valid
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="mt-1 text-sm text-gray-600">
                                                <span>Quotation Date: {{ $quotation->quotation_date->format('M d, Y') }}</span>
                                                <span class="mx-2">•</span>
                                                <span>Valid Until: {{ $quotation->validity_date->format('M d, Y') }}</span>
                                                @if($quotation->supplier_location)
                                                    <span class="mx-2">•</span>
                                                    <span>{{ Str::limit($quotation->supplier_location, 50) }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-4">
                                            <div class="text-right">
                                                <div class="text-2xl font-bold font-mono">₱{{ number_format((float)$quotation->total_amount, 2) }}</div>
                                            </div>
                                            @if($quotation->quotation_file_path)
                                                <a href="{{ asset('storage/' . $quotation->quotation_file_path) }}" target="_blank" 
                                                   class="inline-flex items-center px-3 py-2 bg-gray-600 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                    </svg>
                                                    File
                                                </a>
                                            @endif
                                            <svg class="w-5 h-5 text-gray-400 toggle-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    
                                    <div id="details_{{ $quotation->id }}" class="hidden px-4 py-3 bg-white">
                                        <h5 class="font-semibold text-gray-800 mb-2">Line Items</h5>
                                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Description</th>
                                                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500">Qty</th>
                                                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">ABC (Unit)</th>
                                                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Unit Price</th>
                                                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">Total</th>
                                                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200">
                                                @foreach($quotation->quotationItems as $item)
                                                <tr class="{{ $item->unit_price !== null && !$item->is_within_abc ? 'bg-red-50' : '' }}">
                                                    <td class="px-3 py-2">{{ $item->purchaseRequestItem->item_name }}</td>
                                                    <td class="px-3 py-2 text-center">{{ $item->purchaseRequestItem->quantity_requested }}</td>
                                                    <td class="px-3 py-2 text-right font-mono">₱{{ number_format((float)$item->purchaseRequestItem->estimated_unit_cost, 2) }}</td>
                                                    <td class="px-3 py-2 text-right font-mono font-semibold">
                                                        @if($item->unit_price !== null)
                                                            ₱{{ number_format((float)$item->unit_price, 2) }}
                                                        @else
                                                            <span class="text-gray-400">--</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-2 text-right font-mono font-semibold">
                                                        @if($item->unit_price !== null)
                                                            ₱{{ number_format((float)$item->total_price, 2) }}
                                                        @else
                                                            <span class="text-gray-400">--</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-2 text-center">
                                                        @if($item->unit_price === null)
                                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                                                Not Quoted
                                                            </span>
                                                        @elseif($item->is_within_abc)
                                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                                ✓ Within ABC
                                                            </span>
                                                        @else
                                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                                ⚠ Exceeds ABC
                                                            </span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Abstract of Quotations --}}
                    @if($quotations->count() > 0)
                    <div class="bg-white border border-gray-300 rounded-lg p-6 mb-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-semibold text-xl text-gray-800">Abstract of Quotations</h3>
                            <a href="{{ route('bac.quotations.aoq', $purchaseRequest) }}" 
                               class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                                </svg>
                                Print Abstract
                            </a>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 border text-sm">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th rowspan="2" class="px-3 py-2 text-left text-xs font-semibold text-gray-700 border-r">Item Description</th>
                                        <th rowspan="2" class="px-3 py-2 text-center text-xs font-semibold text-gray-700 border-r">Unit</th>
                                        <th rowspan="2" class="px-3 py-2 text-center text-xs font-semibold text-gray-700 border-r">Qty</th>
                                        <th rowspan="2" class="px-3 py-2 text-right text-xs font-semibold text-gray-700 border-r">ABC</th>
                                        <th colspan="{{ $quotations->count() }}" class="px-3 py-2 text-center text-xs font-semibold text-gray-700">Supplier Quotations (Unit Price)</th>
                                    </tr>
                                    <tr>
                                        @foreach($quotations as $quotation)
                                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-700 border-l">
                                            <div>{{ Str::limit($quotation->supplier->business_name ?? 'N/A', 20) }}</div>
                                            @if($quotation->bac_status === 'lowest_bidder')
                                                <span class="inline-block mt-1 px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">★ Lowest</span>
                                            @endif
                                        </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($purchaseRequest->items as $prItem)
                                    <tr>
                                        <td class="px-3 py-2 border-r">{{ $prItem->item_name }}</td>
                                        <td class="px-3 py-2 text-center border-r">{{ $prItem->unit_of_measure }}</td>
                                        <td class="px-3 py-2 text-center border-r">{{ $prItem->quantity_requested }}</td>
                                        <td class="px-3 py-2 text-right font-mono border-r">₱{{ number_format((float)$prItem->estimated_unit_cost, 2) }}</td>
                                        
                                        @php
                                            $itemQuotations = [];
                                            foreach($quotations as $q) {
                                                $quotItem = $q->quotationItems->firstWhere('purchase_request_item_id', $prItem->id);
                                                $itemQuotations[] = $quotItem;
                                            }
                                            // Only consider non-null prices for lowest price calculation
                                            $lowestPrice = collect($itemQuotations)->filter(function($item) {
                                                return $item && $item->unit_price !== null;
                                            })->min('unit_price');
                                        @endphp
                                        
                                        @foreach($itemQuotations as $quotItem)
                                        <td class="px-3 py-2 text-right font-mono border-l
                                            @if($quotItem && $quotItem->unit_price !== null && $quotItem->unit_price == $lowestPrice) bg-green-50 font-semibold @endif
                                            @if($quotItem && $quotItem->unit_price !== null && !$quotItem->is_within_abc) bg-red-50 @endif">
                                            @if($quotItem && $quotItem->unit_price !== null)
                                                ₱{{ number_format((float)$quotItem->unit_price, 2) }}
                                                @if(!$quotItem->is_within_abc)
                                                    <span class="text-red-600 text-xs">⚠</span>
                                                @endif
                                            @else
                                                <span class="text-gray-400">--</span>
                                            @endif
                                        </td>
                                        @endforeach
                                    </tr>
                                    @endforeach
                                    
                                    <tr class="bg-gray-50 font-semibold text-base">
                                        <td colspan="3" class="px-3 py-3 text-right border-r">TOTAL:</td>
                                        <td class="px-3 py-3 text-right font-mono border-r">₱{{ number_format((float)$purchaseRequest->estimated_total, 2) }}</td>
                                        @foreach($quotations as $quotation)
                                        <td class="px-3 py-3 text-right font-mono text-lg border-l
                                            @if($quotation->bac_status === 'lowest_bidder') bg-green-100 @endif
                                            @if($quotation->exceeds_abc) bg-red-100 @endif">
                                            ₱{{ number_format((float)$quotation->total_amount, 2) }}
                                        </td>
                                        @endforeach
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 text-xs text-gray-600">
                            <p><strong>Legend:</strong></p>
                            <p>• <span class="bg-green-50 px-2 py-1">Green highlight</span> = Lowest price per item</p>
                            <p>• <span class="bg-red-50 px-2 py-1">Red highlight with ⚠</span> = Exceeds ABC (not eligible for award)</p>
                            <p>• <span class="text-gray-400">--</span> = Item not quoted by supplier</p>
                        </div>
                    </div>
                    @endif

                    <!-- {{-- Finalize Abstract Form --}}
                    @if($quotations->count() >= 3)
                    <form action="{{ route('bac.quotations.finalize', $purchaseRequest) }}" method="POST" class="border-t pt-6">
                        @csrf
                        @method('PUT')
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                            <h3 class="font-semibold text-lg text-gray-800 mb-4">Finalize Abstract & Select Winner</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Winning Quotation (Optional)</label>
                                    <select name="winning_quotation_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">-- Select Winner (can be set later) --</option>
                                        @foreach($quotations->where('exceeds_abc', false) as $q)
                                            <option value="{{ $q->id }}" {{ $q->bac_status === 'lowest_bidder' ? 'selected' : '' }}>
                                                {{ $q->supplier->business_name ?? 'N/A' }} - ₱{{ number_format((float)$q->total_amount, 2) }}
                                                @if($q->bac_status === 'lowest_bidder') (Lowest Bidder) @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500">Only ABC-compliant quotations are shown</p>
                                </div>
                                <div class="text-right">
                                    <button type="submit" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white text-base font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Finalize Abstract & Proceed
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                    @endif -->

                </div>
            </div>
        </div>
    </div>

    {{-- Include unchanged modal sections for BAC Resolution and RFQ regeneration --}}
    @include('bac.quotations.partials.resolution-modal')
    @include('bac.quotations.partials.rfq-modal')

    {{-- JavaScript for dynamic calculations --}}
    <script>
        // Auto-fill supplier location when supplier is selected
        document.getElementById('supplier_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const location = selectedOption.getAttribute('data-location');
            document.getElementById('supplier_location').value = location || '';
        });

        // Calculate validity date when quotation date changes
        document.getElementById('quotation_date').addEventListener('change', function() {
            if (this.value) {
                const quotationDate = new Date(this.value);
                const validityDate = new Date(quotationDate);
                validityDate.setDate(validityDate.getDate() + 10);
                
                const formatted = validityDate.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                document.getElementById('validity_date_display').value = formatted;
            }
        });

        // Calculate totals and check ABC compliance for each item
        document.querySelectorAll('.unit-price-input').forEach(input => {
            input.addEventListener('input', function() {
                calculateItemTotal(this);
                calculateGrandTotal();
            });

            // Initialize on page load if value exists
            if (input.value) {
                calculateItemTotal(input);
            }
        });

        function calculateItemTotal(input) {
            const index = input.getAttribute('data-index');
            const qty = parseFloat(input.getAttribute('data-qty'));
            const abc = parseFloat(input.getAttribute('data-abc'));
            const unitPriceValue = input.value.trim();
            
            // Check if the field is empty (supplier didn't quote this item)
            if (unitPriceValue === '' || unitPriceValue === null) {
                // Display as "Not Quoted"
                document.getElementById('total_' + index).textContent = '--';
                document.getElementById('status_' + index).innerHTML = '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Not Quoted</span>';
                input.classList.remove('border-red-500', 'border-green-500');
                return;
            }
            
            const unitPrice = parseFloat(unitPriceValue) || 0;
            const totalPrice = qty * unitPrice;

            // Update total price display
            if (unitPrice === 0) {
                document.getElementById('total_' + index).textContent = '₱0.00';
            } else {
                document.getElementById('total_' + index).textContent = '₱' + totalPrice.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            // Update ABC status
            const statusElement = document.getElementById('status_' + index);
            if (unitPrice === 0) {
                statusElement.innerHTML = '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Free Item</span>';
                input.classList.remove('border-red-500', 'border-green-500');
            } else if (unitPrice <= abc) {
                statusElement.innerHTML = '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">✓ Within ABC</span>';
                input.classList.remove('border-red-500');
                input.classList.add('border-green-500');
            } else {
                const excess = unitPrice - abc;
                statusElement.innerHTML = '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">⚠ +₱' + excess.toFixed(2) + '</span>';
                input.classList.remove('border-green-500');
                input.classList.add('border-red-500');
            }
        }

        function calculateGrandTotal() {
            let grandTotal = 0;
            document.querySelectorAll('.unit-price-input').forEach(input => {
                const unitPriceValue = input.value.trim();
                
                // Skip items that weren't quoted (empty fields)
                if (unitPriceValue === '' || unitPriceValue === null) {
                    return; // Skip this item
                }
                
                const qty = parseFloat(input.getAttribute('data-qty'));
                const unitPrice = parseFloat(unitPriceValue) || 0;
                grandTotal += qty * unitPrice;
            });

            document.getElementById('grand_total').textContent = '₱' + grandTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        // Toggle quotation details
        function toggleQuotationDetails(quotationId) {
            const detailsElement = document.getElementById('details_' + quotationId);
            detailsElement.classList.toggle('hidden');
        }

        // Initialize calculations on page load
        document.addEventListener('DOMContentLoaded', function() {
            calculateGrandTotal();
        });
    </script>
</x-app-layout>
