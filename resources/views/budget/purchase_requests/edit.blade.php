@section('title', 'Budget Office - Earmark Review')

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('Earmark Review: ') . $purchaseRequest->pr_number }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-6">
                    <!-- Requester Information -->
                    <div class="bg-blue-50 p-4 rounded-md">
                        <div class="text-sm font-semibold text-blue-800 mb-2">Requester Information</div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <div class="text-xs text-blue-600">Name</div>
                                <div class="font-medium text-gray-900">{{ $purchaseRequest->requester?->name ?? 'N/A' }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-blue-600">Department</div>
                                <div class="font-medium text-gray-900">{{ $purchaseRequest->department?->name ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- CEO Approval Comments -->
                    @if($ceoApproval && $ceoApproval->comments)
                    <div class="bg-green-50 p-4 rounded-md">
                        <div class="text-sm font-semibold text-green-800 mb-2">CEO Comments</div>
                        <div class="text-gray-900">{{ $ceoApproval->comments }}</div>
                    </div>
                    @endif

                    <div>
                        <div class="flex items-center gap-2">
                            <div class="text-sm text-gray-600">Purpose</div>
                            <button type="button" id="copy-purpose-btn" class="inline-flex items-center text-blue-600 hover:text-blue-800 transition-colors relative group">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 text-xs text-white bg-gray-900 rounded-md opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
                                    Copy purpose to remarks
                                </span>
                            </button>
                        </div>
                        <div class="font-medium" id="purpose-text">{{ $purchaseRequest->purpose }}</div>
                    </div>

                    <div>
                        <div class="text-sm text-gray-600">Estimated Total</div>
                        <div class="font-medium">₱{{ number_format((float)$purchaseRequest->estimated_total, 2) }}</div>
                    </div>

                    <div>
                        <div class="text-sm text-gray-600">Items</div>
                        <ul class="list-disc ml-5">
                            @foreach($purchaseRequest->items as $it)
                            <li>{{ $it->item_name }} ({{ $it->quantity_requested }} x {{ number_format((float)$it->estimated_unit_cost,2) }})</li>
                            @endforeach
                        </ul>
                    </div>

                    <form method="POST" action="{{ route('budget.purchase-requests.update', $purchaseRequest) }}" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div class="border-t pt-4">
                            <h3 class="text-lg font-semibold mb-4">Procurement Details</h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <x-input-label for="date_needed" value="Date" />
                                    <x-text-input id="date_needed" name="date_needed" type="date" class="mt-1 block w-full bg-gray-100"
                                        value="{{ now()->format('Y-m-d') }}" required readonly />
                                    <x-input-error :messages="$errors->get('date_needed')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="funding_source" value="Funding Source" />
                                    <x-text-input id="funding_source" name="funding_source" type="text" class="mt-1 block w-full"
                                        :value="old('funding_source', $purchaseRequest->funding_source)" />
                                    <x-input-error :messages="$errors->get('funding_source')" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <x-input-label for="budget_code" value="Budget Code" />
                                    <x-text-input id="budget_code" name="budget_code" type="text" class="mt-1 block w-full"
                                        :value="old('budget_code', $purchaseRequest->budget_code)" />
                                    <x-input-error :messages="$errors->get('budget_code')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="procurement_type" value="Procurement Type" />
                                    <select id="procurement_type" name="procurement_type" class="mt-1 block w-full border-gray-300 rounded-md" required>
                                        <option value="">Select Type</option>
                                        <option value="supplies_materials" {{ old('procurement_type', $purchaseRequest->procurement_type) == 'supplies_materials' ? 'selected' : '' }}>Supplies/Materials</option>
                                        <option value="equipment" {{ old('procurement_type', $purchaseRequest->procurement_type) == 'equipment' ? 'selected' : '' }}>Equipment</option>
                                        <option value="infrastructure" {{ old('procurement_type', $purchaseRequest->procurement_type) == 'infrastructure' ? 'selected' : '' }}>Infrastructure</option>
                                        <option value="services" {{ old('procurement_type', $purchaseRequest->procurement_type) == 'services' ? 'selected' : '' }}>Services</option>
                                        <option value="consulting_services" {{ old('procurement_type', $purchaseRequest->procurement_type) == 'consulting_services' ? 'selected' : '' }}>Consulting Services</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('procurement_type')" class="mt-2" />
                                </div>
                            </div>

                            <div class="mb-4">
                                <x-input-label for="procurement_method" value="Procurement Method" />
                                <select id="procurement_method" name="procurement_method" class="mt-1 block w-full border-gray-300 rounded-md">
                                    <option value="">Select Method</option>
                                    <option value="small_value_procurement" {{ old('procurement_method', $purchaseRequest->procurement_method) == 'small_value_procurement' ? 'selected' : '' }}>Small Value Procurement</option>
                                    <option value="public_bidding" {{ old('procurement_method', $purchaseRequest->procurement_method) == 'public_bidding' ? 'selected' : '' }}>Public Bidding</option>
                                    <option value="direct_contracting" {{ old('procurement_method', $purchaseRequest->procurement_method) == 'direct_contracting' ? 'selected' : '' }}>Direct Contracting</option>
                                    <option value="negotiated_procurement" {{ old('procurement_method', $purchaseRequest->procurement_method) == 'negotiated_procurement' ? 'selected' : '' }}>Negotiated Procurement</option>
                                </select>
                                <x-input-error :messages="$errors->get('procurement_method')" class="mt-2" />
                            </div>
                        </div>

                        <div class="border-t pt-4">
                            <h3 class="text-lg font-semibold mb-4">Budget Approval</h3>

                            <div>
                                <x-input-label for="approved_budget_total" value="Approved Budget Total (₱)" />
                                <x-text-input id="approved_budget_total" name="approved_budget_total" type="number" step="0.01" class="mt-1 block w-full"
                                    value="{{ old('approved_budget_total', number_format((float)$purchaseRequest->estimated_total,2,'.','')) }}" required />
                                <x-input-error :messages="$errors->get('approved_budget_total')" class="mt-2" />
                            </div>

                            <div class="mt-4">
                                <x-input-label for="remarks" value="Remarks *" />
                                <textarea id="remarks" name="remarks" rows="3" class="mt-1 block w-full border-gray-300 rounded-md" required>{{ old('remarks') }}</textarea>
                                <x-input-error :messages="$errors->get('remarks')" class="mt-2" />
                                <p class="mt-1 text-sm text-gray-500">Remarks are required to forward to BAC.</p>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('budget.purchase-requests.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md">Cancel</a>
                            <button type="button" id="show-rejection-form-btn" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">Reject</button>
                            <x-primary-button>Approve & Forward to BAC</x-primary-button>
                        </div>
                    </form>

                    <!-- Rejection Form (Hidden by default) -->
                    <form method="POST" action="{{ route('budget.purchase-requests.reject', $purchaseRequest) }}" id="rejection-form" class="hidden mt-6 border-t pt-6">
                        @csrf
                        <h3 class="text-lg font-semibold mb-4 text-red-600">Reject Purchase Request</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <x-input-label for="rejection_reason" value="Rejection Reason *" />
                                <textarea id="rejection_reason" name="rejection_reason" rows="4" class="mt-1 block w-full border-gray-300 rounded-md" required placeholder="Provide a detailed reason for rejection (minimum 10 characters)"></textarea>
                                <x-input-error :messages="$errors->get('rejection_reason')" class="mt-2" />
                                <p class="mt-1 text-sm text-gray-500">Please provide a clear and detailed explanation for why this purchase request is being rejected.</p>
                            </div>

                            <div>
                                <x-input-label for="rejection_remarks" value="Additional Remarks" />
                                <textarea id="rejection_remarks" name="remarks" rows="2" class="mt-1 block w-full border-gray-300 rounded-md" placeholder="Optional additional remarks"></textarea>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 mt-4">
                            <button type="button" id="cancel-rejection-btn" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors">Cancel Rejection</button>
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">Confirm Rejection</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Rejection Reason Modal -->
    <x-rejection-reason-modal id="rejection-modal" />

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const showRejectionBtn = document.getElementById('show-rejection-form-btn');
            const cancelRejectionBtn = document.getElementById('cancel-rejection-btn');
            const rejectionForm = document.getElementById('rejection-form');
            const rejectionReasonField = document.getElementById('rejection_reason');
            const modal = document.getElementById('rejection-modal');
            const modalCloseBtn = modal ? modal.querySelector('[data-modal-close]') : null;
            const copyPurposeBtn = document.getElementById('copy-purpose-btn');
            const purposeText = document.getElementById('purpose-text');
            const remarksField = document.getElementById('remarks');
            const approvalForm = document.querySelector('form[action*="update"]');

            // Copy purpose to remarks
            if (copyPurposeBtn && purposeText && remarksField) {
                copyPurposeBtn.addEventListener('click', function() {
                    remarksField.value = purposeText.textContent.trim();
                    remarksField.focus();
                    
                    // Visual feedback
                    copyPurposeBtn.classList.add('text-green-600');
                    setTimeout(() => {
                        copyPurposeBtn.classList.remove('text-green-600');
                        copyPurposeBtn.classList.add('text-blue-600');
                    }, 500);
                });
            }

            // Validate remarks before submitting approval form
            if (approvalForm) {
                approvalForm.addEventListener('submit', function(e) {
                    const remarks = remarksField.value.trim();
                    if (remarks.length === 0) {
                        e.preventDefault();
                        alert('Remarks are required. Please provide remarks before forwarding to BAC.');
                        remarksField.focus();
                        return false;
                    }
                });
            }

            // Show rejection form
            if (showRejectionBtn) {
                showRejectionBtn.addEventListener('click', function() {
                    rejectionForm.classList.remove('hidden');
                    rejectionForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    rejectionReasonField.focus();
                });
            }

            // Cancel rejection - hide form
            if (cancelRejectionBtn) {
                cancelRejectionBtn.addEventListener('click', function() {
                    rejectionForm.classList.add('hidden');
                    rejectionReasonField.value = '';
                    document.getElementById('rejection_remarks').value = '';
                });
            }

            // Validate rejection reason before submit
            if (rejectionForm) {
                rejectionForm.addEventListener('submit', function(e) {
                    const reason = rejectionReasonField.value.trim();
                    if (reason.length < 10) {
                        e.preventDefault();
                        if (modal) {
                            modal.classList.remove('hidden');
                        }
                        rejectionReasonField.focus();
                    }
                });
            }

            // Close modal
            if (modalCloseBtn && modal) {
                modalCloseBtn.addEventListener('click', function() {
                    modal.classList.add('hidden');
                });
            }

            // Close modal on background click
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        modal.classList.add('hidden');
                    }
                });
            }
        });
    </script>
</x-app-layout>