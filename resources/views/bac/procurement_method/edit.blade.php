@section('title', 'BAC - Set Procurement Method')

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('Set Procurement Method: ') . $purchaseRequest->pr_number }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-6">
                    @if(session('status'))
                        <div class="mb-4 p-3 rounded-md bg-green-50 text-green-700">{{ session('status') }}</div>
                    @endif

                    @if(session('error'))
                        <div class="mb-4 p-3 rounded-md bg-red-50 text-red-700">{{ session('error') }}</div>
                    @endif

                    <!-- Approval Trail -->
                    <div class="bg-blue-50 p-4 rounded-md space-y-3">
                        <div class="text-sm font-semibold text-blue-800 mb-2">Approval History</div>
                        
                        @if($budgetApproval && $budgetApproval->status === 'approved')
                        <div class="flex items-start space-x-3">
                            <svg class="w-5 h-5 text-green-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <div class="flex-1">
                                <div class="font-medium text-gray-900">Budget Office Approved</div>
                                <div class="text-sm text-gray-600">Earmark ID: {{ $purchaseRequest->earmark_id }}</div>
                                @if($budgetApproval->remarks)
                                <div class="text-sm text-gray-600 mt-1">Remarks: {{ $budgetApproval->remarks }}</div>
                                @endif
                            </div>
                        </div>
                        @endif

                        @if($ceoApproval && $ceoApproval->status === 'approved')
                        <div class="flex items-start space-x-3">
                            <svg class="w-5 h-5 text-green-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <div class="flex-1">
                                <div class="font-medium text-gray-900">CEO Approved</div>
                                @if($ceoApproval->comments)
                                <div class="text-sm text-gray-600 mt-1">Comments: {{ $ceoApproval->comments }}</div>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- PR Details -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="text-sm text-gray-600">Department</div>
                            <div class="font-medium">{{ $purchaseRequest->department?->name ?? 'N/A' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600">Requester</div>
                            <div class="font-medium">{{ $purchaseRequest->requester?->name ?? 'N/A' }}</div>
                        </div>
                    </div>

                    <div>
                        <div class="text-sm text-gray-600">Purpose</div>
                        <div class="font-medium">{{ $purchaseRequest->purpose }}</div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="text-sm text-gray-600">Approved Budget</div>
                            <div class="font-semibold text-lg text-gray-900">₱{{ number_format((float)$purchaseRequest->estimated_total, 2) }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600">Procurement Type</div>
                            <div class="font-medium capitalize">{{ str_replace('_', ' ', $purchaseRequest->procurement_type ?? 'N/A') }}</div>
                        </div>
                    </div>

                    <!-- Items -->
                    <div>
                        <div class="text-sm text-gray-600 mb-2">Items Requested</div>
                        <div class="border rounded-md overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Unit Cost</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($purchaseRequest->items as $item)
                                    <tr>
                                        <td class="px-4 py-2 text-sm">{{ $item->item_name }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $item->quantity_requested }}</td>
                                        <td class="px-4 py-2 text-sm">₱{{ number_format((float)$item->estimated_unit_cost, 2) }}</td>
                                        <td class="px-4 py-2 text-sm font-medium">₱{{ number_format((float)$item->estimated_total_cost, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Procurement Method Selection Form -->
                    <form method="POST" action="{{ route('bac.procurement-method.update', $purchaseRequest) }}" class="border-t pt-6 space-y-4">
                        @csrf
                        @method('PUT')

                        <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800">Determine Procurement Method</h3>
                                    <div class="mt-2 text-sm text-yellow-700">
                                        <p>Based on the approved budget and procurement type, select the appropriate procurement method. Once set, a BAC resolution will be automatically generated.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <x-input-label for="procurement_method" value="Procurement Method *" />
                            <select id="procurement_method" name="procurement_method" class="mt-1 block w-full border-gray-300 rounded-md" required>
                                <option value="">Select Procurement Method</option>
                                <option value="small_value_procurement">Small Value Procurement (Below ₱1,000,000)</option>
                                <option value="public_bidding">Public Bidding (₱1,000,000 and above)</option>
                                <option value="direct_contracting">Direct Contracting (Special circumstances)</option>
                                <option value="negotiated_procurement">Negotiated Procurement (Emergency/Exclusive)</option>
                            </select>
                            <x-input-error :messages="$errors->get('procurement_method')" class="mt-2" />
                            
                            <!-- Helper text for each method -->
                            <div id="method-info" class="mt-2 text-sm text-gray-600 hidden"></div>
                        </div>

                        <div>
                            <x-input-label for="remarks" value="Remarks (Optional)" />
                            <textarea id="remarks" name="remarks" rows="3" class="mt-1 block w-full border-gray-300 rounded-md" placeholder="Add any notes or justification for the selected method">{{ old('remarks') }}</textarea>
                            <x-input-error :messages="$errors->get('remarks')" class="mt-2" />
                        </div>

                        <!-- Signatory Selection -->
                        @include('bac.partials.signatory_form', [
                            'signatories' => $purchaseRequest->resolutionSignatories ?? null,
                            'bacSignatories' => $bacSignatories ?? []
                        ])

                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('bac.procurement-method.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors">Cancel</a>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                Set Method & Generate Resolution
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const methodSelect = document.getElementById('procurement_method');
            const methodInfo = document.getElementById('method-info');
            
            const methodDescriptions = {
                'small_value_procurement': 'Used for procurement of goods, infrastructure projects, and consulting services where the ABC does not exceed One Million Pesos (₱1,000,000.00).',
                'public_bidding': 'Default method for procurement of goods, infrastructure projects, and consulting services where the ABC exceeds One Million Pesos (₱1,000,000.00).',
                'direct_contracting': 'May be resorted to in highly exceptional cases where direct contracting is the most advantageous to the government.',
                'negotiated_procurement': 'May be resorted to in emergency cases or when goods are available from exclusive dealers or manufacturers.'
            };
            
            methodSelect.addEventListener('change', function() {
                const selectedMethod = this.value;
                if (selectedMethod && methodDescriptions[selectedMethod]) {
                    methodInfo.textContent = methodDescriptions[selectedMethod];
                    methodInfo.classList.remove('hidden');
                } else {
                    methodInfo.classList.add('hidden');
                }
            });
        });
    </script>
</x-app-layout>

