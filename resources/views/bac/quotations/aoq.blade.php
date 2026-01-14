@section('title', 'BAC - Abstract of Quotations')

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
            {{ __('Abstract of Quotations: ') . $purchaseRequest->pr_number }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            @if(session('status'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                    {{ session('status') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Failed Items Alert --}}
            @php
                $failedItems = $purchaseRequest->items->where('procurement_status', 'failed');
                $failedItemsNeedingRePr = $failedItems->whereNull('replacement_pr_id');
            @endphp
            @if($failedItems->isNotEmpty())
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-red-600 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <div class="flex-1">
                            <h4 class="font-semibold text-red-800">Failed Procurement Items</h4>
                            <p class="text-sm text-red-700 mt-1">
                                {{ $failedItems->count() }} item(s) have failed procurement due to supplier withdrawals.
                            </p>
                            <ul class="list-disc list-inside text-sm text-red-700 mt-2 space-y-1">
                                @foreach($failedItems as $failedItem)
                                    <li>
                                        {{ $failedItem->item_name }}
                                        @if($failedItem->replacement_pr_id)
                                            <span class="text-green-700">(Re-PR Created: {{ $failedItem->replacementPr->pr_number }})</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                            @if($failedItemsNeedingRePr->isNotEmpty())
                                <form action="{{ route('bac.quotations.create-replacement-pr', $purchaseRequest) }}" method="POST" class="mt-3">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white text-sm rounded hover:bg-red-700">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        Create Replacement PR for {{ $failedItemsNeedingRePr->count() }} Item(s)
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- AOQ Status Card --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">AOQ Status</h3>
                    
                    @if($validation['can_generate'])
                        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="font-semibold">Ready to Generate AOQ</span>
                            </div>
                            <p class="mt-2 text-sm">All items have been evaluated and all ties have been resolved.</p>
                        </div>
                        
                        <button type="button" 
                                onclick="document.getElementById('generateAoqModal').classList.remove('hidden')"
                                class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Generate AOQ Document
                        </button>
                    @else
                        <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg">
                            <div class="flex items-center mb-2">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="font-semibold">Cannot Generate AOQ Yet</span>
                            </div>
                            <ul class="list-disc list-inside text-sm space-y-1">
                                @foreach($validation['errors'] as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Previously Generated AOQs --}}
            @if($aoqGenerations->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Previously Generated AOQs</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference Number</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Generated By</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items/Suppliers</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($aoqGenerations as $generation)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap font-semibold text-blue-600">{{ $generation->aoq_reference_number }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">{{ $generation->generatedBy->name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $generation->created_at->format('M d, Y h:i A') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $generation->total_items }} items / {{ $generation->total_suppliers }} suppliers</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <a href="{{ route('bac.quotations.aoq.download', [$purchaseRequest, $generation->id]) }}" class="text-blue-600 hover:text-blue-900">
                                                    Download
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            {{-- AOQ Data Table --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Quotation Evaluation</h3>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                                    @foreach($quotations as $quotation)
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">
                                            {{ $quotation->supplier->business_name }}
                                        </th>
                                    @endforeach
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Decision</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($aoqData as $itemData)
                                    @php
                                        $item = $itemData['item'];
                                        $hasTie = $itemData['has_tie'];
                                        $winners = $itemData['winners'];
                                    @endphp
                                    <tr>
                                        <td class="px-4 py-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $item->item_name }}</div>
                                            <div class="text-xs text-gray-500">{{ $item->unit_of_measure }}</div>
                                        </td>
                                        <td class="px-4 py-4 text-sm text-gray-900">{{ $item->quantity_requested }}</td>
                                        
                                        @foreach($quotations as $quotation)
                                            @php
                                                $quoteItem = collect($itemData['quotes'])->firstWhere('quotation.id', $quotation->id);
                                            @endphp
                                            <td class="px-4 py-4 text-center">
                                                @if($quoteItem)
                                                    @php
                                                        $qi = $quoteItem['quotation_item'];
                                                        $bgColor = '';
                                                        $badgeColor = 'bg-gray-500';
                                                        
                                                        if ($qi->is_withdrawn) {
                                                            $bgColor = 'bg-orange-50';
                                                            $badgeColor = 'bg-orange-600';
                                                        } elseif ($qi->isDisqualified()) {
                                                            $bgColor = 'bg-red-100';
                                                            $badgeColor = 'bg-red-600';
                                                        } elseif ($qi->is_winner) {
                                                            $bgColor = 'bg-green-100';
                                                            $badgeColor = 'bg-green-600';
                                                        } elseif ($qi->is_tied) {
                                                            $bgColor = 'bg-yellow-100';
                                                            $badgeColor = 'bg-yellow-600';
                                                        } elseif ($qi->is_lowest) {
                                                            $bgColor = 'bg-blue-50';
                                                            $badgeColor = 'bg-blue-600';
                                                        }
                                                    @endphp
                                                    <div class="{{$bgColor}} p-2 rounded">
                                                        <div class="text-sm font-semibold {{ $qi->is_withdrawn ? 'line-through text-gray-500' : '' }}">
                                                            ₱ {{ number_format($qi->total_price, 2) }}
                                                        </div>
                                                        <div class="text-xs text-gray-600 {{ $qi->is_withdrawn ? 'line-through' : '' }}">
                                                            ₱ {{ number_format($qi->unit_price, 2) }} each
                                                        </div>
                                                        <div class="text-xs mt-1">
                                                            <span class="px-2 py-1 rounded text-white text-xs {{$badgeColor}}">
                                                                {{ $qi->getAoqStatusLabel() }}
                                                            </span>
                                                        </div>
                                                        @if($qi->is_withdrawn)
                                                            <div class="text-xs text-orange-700 mt-1 font-semibold">
                                                                Withdrawn: {{ Str::limit($qi->withdrawal_reason, 30) }}
                                                            </div>
                                                        @elseif($qi->isDisqualified())
                                                            <div class="text-xs text-red-700 mt-1 font-semibold">{{ $qi->disqualification_reason }}</div>
                                                        @elseif($qi->rank)
                                                            <div class="text-xs text-gray-500 mt-1">Rank: {{ $qi->rank }}</div>
                                                        @endif
                                                        
                                                        {{-- Withdraw button for winners --}}
                                                        @if($qi->is_winner && !$qi->is_withdrawn)
                                                            <button type="button"
                                                                onclick="openWithdrawalModal({{ $qi->id }}, '{{ $quotation->supplier->business_name }}', '{{ addslashes($item->item_name) }}', {{ $qi->unit_price }}, {{ $qi->total_price }})"
                                                                class="mt-2 px-2 py-1 bg-orange-500 text-white text-xs rounded hover:bg-orange-600 transition-colors">
                                                                Withdraw
                                                            </button>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="text-gray-400 text-sm">No quote</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        
                                        <td class="px-4 py-4">
                                            @if($item->procurement_status === 'failed')
                                                <div class="text-sm">
                                                    <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs font-semibold">
                                                        Failed Procurement
                                                    </span>
                                                    @if($item->replacement_pr_id)
                                                        <div class="text-xs text-gray-600 mt-1">
                                                            Re-PR: <a href="{{ route('bac.quotations.manage', $item->replacementPr) }}" class="text-blue-600 hover:underline">{{ $item->replacementPr->pr_number }}</a>
                                                        </div>
                                                    @endif
                                                </div>
                                            @elseif($hasTie)
                                                <button 
                                                    onclick="openTieResolutionModal({{ $item->id }}, '{{ addslashes($item->item_name) }}', {{ json_encode($itemData['quotes']) }})"
                                                    class="px-3 py-1 bg-yellow-600 text-white text-xs rounded hover:bg-yellow-700">
                                                    Resolve Tie
                                                </button>
                                            @elseif(count($winners) > 0)
                                                @php
                                                    $winner = reset($winners);
                                                    $winnerSupplier = $winner['quotation']->supplier->business_name;
                                                @endphp
                                                <div class="text-sm">
                                                    <span class="font-semibold text-green-600">{{ $winnerSupplier }}</span>
                                                    <button 
                                                        onclick="openBacOverrideModal({{ $item->id }}, '{{ addslashes($item->item_name) }}', {{ json_encode($itemData['quotes']) }})"
                                                        class="ml-2 px-2 py-1 bg-orange-600 text-white text-xs rounded hover:bg-orange-700">
                                                        Override
                                                    </button>
                                                </div>
                                            @elseif(!isset($itemData['has_eligible_bidders']) || !$itemData['has_eligible_bidders'])
                                                <div class="text-sm">
                                                    <span class="text-red-600 font-semibold">No eligible bidders</span>
                                                    <form action="{{ route('bac.pr-items.mark-failed', $item) }}" method="POST" class="mt-1 inline-block"
                                                          onsubmit="return confirm('Are you sure you want to mark this item as failed procurement? This action cannot be undone.')">
                                                        @csrf
                                                        <input type="hidden" name="failure_reason" value="All suppliers withdrew or were disqualified">
                                                        <button type="submit" class="px-2 py-1 bg-red-600 text-white text-xs rounded hover:bg-red-700">
                                                            Mark as Failed
                                                        </button>
                                                    </form>
                                                </div>
                                            @else
                                                <span class="text-gray-400 text-sm">No winner</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="flex justify-between">
                <a href="{{ route('bac.quotations.manage', $purchaseRequest) }}" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                    Back to Quotations
                </a>
            </div>
        </div>
    </div>

    {{-- Tie Resolution Modal --}}
    <div id="tieResolutionModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Resolve Tie</h3>
                <form id="tieResolutionForm" method="POST" action="{{ route('bac.quotations.aoq.resolve-tie', $purchaseRequest) }}">
                    @csrf
                    <input type="hidden" name="purchase_request_item_id" id="tie_item_id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Item:</label>
                        <p id="tie_item_name" class="text-gray-900"></p>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Winner:</label>
                        <select name="winning_quotation_item_id" id="tie_winner_select" required class="w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">-- Select Supplier --</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Justification: <span class="text-red-500">*</span></label>
                        <textarea name="justification" required minlength="10" maxlength="1000" rows="4" 
                            class="w-full border-gray-300 rounded-md shadow-sm" 
                            placeholder="Explain why this supplier was selected..."></textarea>
                        <p class="text-xs text-gray-500 mt-1">Minimum 10 characters required</p>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeTieResolutionModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                            Save Decision
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Supplier Withdrawal Modal --}}
    <div id="withdrawalModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Process Supplier Withdrawal</h3>
                <div class="bg-orange-50 border border-orange-200 text-orange-800 px-4 py-3 rounded-lg mb-4">
                    <p class="text-sm font-semibold">Warning: This will withdraw the winning bid from this supplier.</p>
                    <p class="text-xs mt-1">The next eligible bidder will automatically become the winner. If no eligible bidders remain, the item will be marked as failed procurement.</p>
                </div>
                
                {{-- Withdrawal Preview --}}
                <div id="withdrawal_preview" class="mb-4 p-4 bg-gray-50 rounded-lg">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">Supplier:</span>
                            <span id="withdrawal_supplier" class="font-semibold text-gray-900 ml-2"></span>
                        </div>
                        <div>
                            <span class="text-gray-500">Item:</span>
                            <span id="withdrawal_item" class="font-semibold text-gray-900 ml-2"></span>
                        </div>
                        <div>
                            <span class="text-gray-500">Unit Price:</span>
                            <span id="withdrawal_unit_price" class="font-semibold text-gray-900 ml-2"></span>
                        </div>
                        <div>
                            <span class="text-gray-500">Total Price:</span>
                            <span id="withdrawal_total_price" class="font-semibold text-gray-900 ml-2"></span>
                        </div>
                    </div>
                    <div id="withdrawal_next_bidder_info" class="mt-4 pt-4 border-t border-gray-200 hidden">
                        <p class="text-sm text-gray-600">Next eligible bidder will be: <span id="next_bidder_name" class="font-semibold text-green-600"></span></p>
                    </div>
                    <div id="withdrawal_failure_warning" class="mt-4 pt-4 border-t border-gray-200 hidden">
                        <p class="text-sm text-red-600 font-semibold">⚠️ No eligible bidders remaining. This item will be marked as failed procurement.</p>
                    </div>
                </div>
                
                <form id="withdrawalForm" method="POST" action="">
                    @csrf
                    <input type="hidden" name="quotation_item_id" id="withdrawal_quotation_item_id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Withdrawal Reason: <span class="text-red-500">*</span></label>
                        <textarea name="withdrawal_reason" required minlength="10" maxlength="1000" rows="4" 
                            class="w-full border-gray-300 rounded-md shadow-sm" 
                            placeholder="Explain why this supplier is withdrawing their bid..."></textarea>
                        <p class="text-xs text-gray-500 mt-1">Minimum 10 characters required</p>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeWithdrawalModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700">
                            Process Withdrawal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- BAC Override Modal --}}
    <div id="bacOverrideModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">BAC Override</h3>
                <div class="bg-orange-50 border border-orange-200 text-orange-800 px-4 py-3 rounded-lg mb-4">
                    <p class="text-sm font-semibold">Warning: This will override the automatically determined winner.</p>
                    <p class="text-xs mt-1">Provide a detailed justification for audit purposes.</p>
                </div>
                <form id="bacOverrideForm" method="POST" action="{{ route('bac.quotations.aoq.bac-override', $purchaseRequest) }}">
                    @csrf
                    <input type="hidden" name="purchase_request_item_id" id="override_item_id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Item:</label>
                        <p id="override_item_name" class="text-gray-900"></p>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select New Winner:</label>
                        <select name="winning_quotation_item_id" id="override_winner_select" required class="w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">-- Select Supplier --</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Justification: <span class="text-red-500">*</span></label>
                        <textarea name="justification" required minlength="20" maxlength="1000" rows="4" 
                            class="w-full border-gray-300 rounded-md shadow-sm" 
                            placeholder="Provide detailed justification for this override decision..."></textarea>
                        <p class="text-xs text-gray-500 mt-1">Minimum 20 characters required for override justification</p>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeBacOverrideModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700">
                            Apply Override
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openWithdrawalModal(quotationItemId, supplierName, itemName, unitPrice, totalPrice) {
            document.getElementById('withdrawal_quotation_item_id').value = quotationItemId;
            document.getElementById('withdrawal_supplier').textContent = supplierName;
            document.getElementById('withdrawal_item').textContent = itemName;
            document.getElementById('withdrawal_unit_price').textContent = '₱ ' + parseFloat(unitPrice).toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('withdrawal_total_price').textContent = '₱ ' + parseFloat(totalPrice).toLocaleString('en-US', {minimumFractionDigits: 2});
            
            // Set form action
            document.getElementById('withdrawalForm').action = `/bac/quotation-items/${quotationItemId}/withdraw`;
            
            // Fetch withdrawal preview
            fetch(`/bac/quotation-items/${quotationItemId}/withdrawal-preview`)
                .then(response => response.json())
                .then(data => {
                    const nextBidderInfo = document.getElementById('withdrawal_next_bidder_info');
                    const failureWarning = document.getElementById('withdrawal_failure_warning');
                    
                    if (data.would_cause_failure) {
                        nextBidderInfo.classList.add('hidden');
                        failureWarning.classList.remove('hidden');
                    } else if (data.next_bidder) {
                        document.getElementById('next_bidder_name').textContent = 
                            `${data.next_bidder.supplier_name} (₱${parseFloat(data.next_bidder.total_price).toLocaleString('en-US', {minimumFractionDigits: 2})})`;
                        nextBidderInfo.classList.remove('hidden');
                        failureWarning.classList.add('hidden');
                    } else {
                        nextBidderInfo.classList.add('hidden');
                        failureWarning.classList.add('hidden');
                    }
                })
                .catch(error => {
                    console.error('Error fetching withdrawal preview:', error);
                });
            
            document.getElementById('withdrawalModal').classList.remove('hidden');
        }
        
        function closeWithdrawalModal() {
            document.getElementById('withdrawalModal').classList.add('hidden');
            document.getElementById('withdrawalForm').reset();
            document.getElementById('withdrawal_next_bidder_info').classList.add('hidden');
            document.getElementById('withdrawal_failure_warning').classList.add('hidden');
        }
        
        function openTieResolutionModal(itemId, itemName, quotes) {
            document.getElementById('tie_item_id').value = itemId;
            document.getElementById('tie_item_name').textContent = itemName;
            
            const select = document.getElementById('tie_winner_select');
            select.innerHTML = '<option value="">-- Select Supplier --</option>';
            
            quotes.forEach(quote => {
                // Only show tied/lowest quotes that are NOT disqualified
                const isDisqualified = quote.quotation_item.disqualification_reason && quote.quotation_item.disqualification_reason.length > 0;
                
                if ((quote.quotation_item.is_tied || quote.quotation_item.is_lowest) && !isDisqualified) {
                    const option = document.createElement('option');
                    option.value = quote.quotation_item.id;
                    option.textContent = `${quote.quotation.supplier.business_name} - ₱${parseFloat(quote.total_price).toLocaleString('en-US', {minimumFractionDigits: 2})}`;
                    select.appendChild(option);
                }
            });
            
            document.getElementById('tieResolutionModal').classList.remove('hidden');
        }
        
        function closeTieResolutionModal() {
            document.getElementById('tieResolutionModal').classList.add('hidden');
            document.getElementById('tieResolutionForm').reset();
        }
        
        function openBacOverrideModal(itemId, itemName, quotes) {
            document.getElementById('override_item_id').value = itemId;
            document.getElementById('override_item_name').textContent = itemName;
            
            const select = document.getElementById('override_winner_select');
            select.innerHTML = '<option value="">-- Select Supplier --</option>';
            
            quotes.forEach(quote => {
                // Only show non-disqualified quotes
                const isDisqualified = quote.quotation_item.disqualification_reason && quote.quotation_item.disqualification_reason.length > 0;
                
                if (!isDisqualified) {
                    const option = document.createElement('option');
                    option.value = quote.quotation_item.id;
                    const isWinner = quote.quotation_item.is_winner ? ' (Current Winner)' : '';
                    option.textContent = `${quote.quotation.supplier.business_name} - ₱${parseFloat(quote.total_price).toLocaleString('en-US', {minimumFractionDigits: 2})}${isWinner}`;
                    select.appendChild(option);
                }
            });
            
            document.getElementById('bacOverrideModal').classList.remove('hidden');
        }
        
        function closeBacOverrideModal() {
            document.getElementById('bacOverrideModal').classList.add('hidden');
            document.getElementById('bacOverrideForm').reset();
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const tieModal = document.getElementById('tieResolutionModal');
            const overrideModal = document.getElementById('bacOverrideModal');
            const generateAoqModal = document.getElementById('generateAoqModal');
            const withdrawalModal = document.getElementById('withdrawalModal');
            if (event.target === tieModal) {
                closeTieResolutionModal();
            }
            if (event.target === overrideModal) {
                closeBacOverrideModal();
            }
            if (event.target === generateAoqModal) {
                closeGenerateAoqModal();
            }
            if (event.target === withdrawalModal) {
                closeWithdrawalModal();
            }
        }
        
        // Generate AOQ Modal functions
        function closeGenerateAoqModal() {
            document.getElementById('generateAoqModal').classList.add('hidden');
        }
        
        function aoqToggleInputMode(position, mode) {
            const selectSection = document.getElementById(`${position}-select-section`);
            const manualSection = document.getElementById(`${position}-manual-section`);
            const dropdown = document.getElementById(`${position}-select-dropdown`);
            const hiddenNameField = document.getElementById(`${position}-selected-name`);
            
            if (!selectSection || !manualSection) {
                return;
            }
            
            if (mode === 'select') {
                selectSection.classList.remove('hidden');
                manualSection.classList.add('hidden');
                if (dropdown) {
                    aoqHandleSignatorySelection(position);
                }
            } else {
                selectSection.classList.add('hidden');
                manualSection.classList.remove('hidden');
                if (dropdown) {
                    dropdown.value = '';
                }
                if (hiddenNameField) {
                    hiddenNameField.value = '';
                }
                const prefixField = document.getElementById(`${position}-prefix-field`);
                const suffixField = document.getElementById(`${position}-suffix-field`);
                if (prefixField) {
                    prefixField.removeAttribute('readonly');
                    prefixField.classList.remove('bg-gray-100', 'cursor-not-allowed');
                }
                if (suffixField) {
                    suffixField.removeAttribute('readonly');
                    suffixField.classList.remove('bg-gray-100', 'cursor-not-allowed');
                }
            }
        }
        
        function aoqHandleSignatorySelection(position) {
            const dropdown = document.getElementById(`${position}-select-dropdown`);
            const hiddenNameField = document.getElementById(`${position}-selected-name`);
            const prefixField = document.getElementById(`${position}-prefix-field`);
            const suffixField = document.getElementById(`${position}-suffix-field`);
            
            if (!dropdown) {
                return;
            }
            
            const selectedOption = dropdown.options[dropdown.selectedIndex];
            
            if (!selectedOption || dropdown.selectedIndex === 0) {
                if (hiddenNameField) hiddenNameField.value = '';
                if (prefixField) {
                    prefixField.removeAttribute('readonly');
                    prefixField.classList.remove('bg-gray-100', 'cursor-not-allowed');
                }
                if (suffixField) {
                    suffixField.removeAttribute('readonly');
                    suffixField.classList.remove('bg-gray-100', 'cursor-not-allowed');
                }
                return;
            }
            
            const isPreconfigured = selectedOption.getAttribute('data-signatory-id');
            const manualName = selectedOption.getAttribute('data-manual-name') || '';
            const prefix = selectedOption.getAttribute('data-prefix') || '';
            const suffix = selectedOption.getAttribute('data-suffix') || '';
            const hasUser = selectedOption.getAttribute('data-has-user') === 'true';
            
            if (hiddenNameField) {
                hiddenNameField.value = !hasUser && manualName ? manualName : '';
            }
            
            if (isPreconfigured) {
                if (prefixField) {
                    prefixField.value = prefix;
                    prefixField.setAttribute('readonly', 'readonly');
                    prefixField.classList.add('bg-gray-100', 'cursor-not-allowed');
                }
                if (suffixField) {
                    suffixField.value = suffix;
                    suffixField.setAttribute('readonly', 'readonly');
                    suffixField.classList.add('bg-gray-100', 'cursor-not-allowed');
                }
            } else {
                if (prefixField) {
                    prefixField.removeAttribute('readonly');
                    prefixField.classList.remove('bg-gray-100', 'cursor-not-allowed');
                }
                if (suffixField) {
                    suffixField.removeAttribute('readonly');
                    suffixField.classList.remove('bg-gray-100', 'cursor-not-allowed');
                }
            }
        }
        
        document.addEventListener('DOMContentLoaded', () => {
            const positions = ['bac_chairman', 'bac_vice_chairman', 'bac_member_1', 'bac_member_2', 'bac_member_3', 'head_bac_secretariat', 'ceo'];
            positions.forEach(position => {
                const selectedRadio = document.querySelector(`input[name="signatories[${position}][input_mode]"]:checked`);
                if (selectedRadio) {
                    aoqToggleInputMode(position, selectedRadio.value);
                }
                aoqHandleSignatorySelection(position);
            });
        });
    </script>

    @php
        $signatoryDefaults = $signatoryDefaults ?? [];
        $bacSignatoryOptions = $bacSignatoryOptions ?? [];
        $eligibleSignatoryUsers = $eligibleSignatoryUsers ?? collect();
    @endphp

    {{-- Generate AOQ Modal --}}
    <div id="generateAoqModal" class="hidden fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeGenerateAoqModal()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <form action="{{ route('bac.quotations.aoq.generate', $purchaseRequest) }}" method="POST" id="generateAoqForm">
                    @csrf
                    
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    Generate Abstract of Quotations
                                </h3>
                                <div class="mt-4">
                                    <!-- Auto-Apply Notice -->
                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                                        <div class="flex items-start space-x-3">
                                            <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <div class="flex-1">
                                                <h4 class="text-sm font-semibold text-blue-900 mb-1">Signatories Auto-Applied</h4>
                                                <p class="text-sm text-blue-800">Signatories will be automatically applied from your <a href="{{ route('bac.signatories.index') }}" target="_blank" class="underline font-medium hover:text-blue-900">BAC Signatories Setup</a>. You can optionally customize them below if needed.</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Optional Override Section (Collapsible) -->
                                    <details class="mb-4">
                                        <summary class="cursor-pointer text-sm font-medium text-gray-700 hover:text-gray-900 mb-2">
                                            Override Signatories (Optional)
                                        </summary>
                                        <div class="mt-4 pl-4 border-l-2 border-gray-200">

                                    @php
                                        $positions = [
                                            ['key' => 'bac_chairman', 'label' => 'BAC Chairman'],
                                            ['key' => 'bac_vice_chairman', 'label' => 'BAC Vice Chairman'],
                                            ['key' => 'bac_member_1', 'label' => 'BAC Member 1'],
                                            ['key' => 'bac_member_2', 'label' => 'BAC Member 2'],
                                            ['key' => 'bac_member_3', 'label' => 'BAC Member 3'],
                                            ['key' => 'head_bac_secretariat', 'label' => 'Head - BAC Secretariat'],
                                            ['key' => 'ceo', 'label' => 'Campus Executive Officer'],
                                        ];
                                    @endphp

                                    @foreach($positions as $pos)
                                    @php
                                        $positionKey = str_replace(['_1','_2','_3'], '', $pos['key']);
                                        $preconfigured = $bacSignatoryOptions[$positionKey] ?? collect();
                                        $defaults = $signatoryDefaults[$pos['key']] ?? [];
                                        $inputMode = old("signatories.{$pos['key']}.input_mode", $defaults['input_mode'] ?? 'select');
                                        $selectedUserId = old("signatories.{$pos['key']}.user_id", $defaults['user_id'] ?? '');
                                        $selectedSignatoryId = $defaults['bac_signatory_id'] ?? null;
                                        $manualValue = old(
                                            "signatories.{$pos['key']}.name",
                                            $defaults['manual_name'] ?? ($defaults['display_name'] ?? '')
                                        );
                                        $prefixValue = old("signatories.{$pos['key']}.prefix", $defaults['prefix'] ?? '');
                                        $suffixValue = old("signatories.{$pos['key']}.suffix", $defaults['suffix'] ?? '');
                                        $selectedNameValue = old("signatories.{$pos['key']}.selected_name", $defaults['manual_name'] ?? '');
                                    @endphp
                                    <div class="mb-6 border-b pb-4">
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">{{ $pos['label'] }}</label>
                                        
                                        {{-- Input Mode Selection --}}
                                        <div class="flex items-center space-x-4 mb-3">
                                            <label class="inline-flex items-center">
                                                <input type="radio" name="signatories[{{ $pos['key'] }}][input_mode]" value="select" 
                                                       {{ $inputMode === 'select' ? 'checked' : '' }} onchange="aoqToggleInputMode('{{ $pos['key'] }}', 'select')"
                                                       class="form-radio h-4 w-4 text-blue-600">
                                                <span class="ml-2 text-sm">Select from list</span>
                                            </label>
                                            <label class="inline-flex items-center">
                                                <input type="radio" name="signatories[{{ $pos['key'] }}][input_mode]" value="manual"
                                                       {{ $inputMode === 'manual' ? 'checked' : '' }} onchange="aoqToggleInputMode('{{ $pos['key'] }}', 'manual')"
                                                       class="form-radio h-4 w-4 text-blue-600">
                                                <span class="ml-2 text-sm">Enter manually</span>
                                            </label>
                                        </div>

                                        {{-- Select from List --}}
                                        <div id="{{ $pos['key'] }}-select-section" class="{{ $inputMode === 'manual' ? 'hidden' : '' }}">
                                            <select name="signatories[{{ $pos['key'] }}][user_id]" 
                                                    id="{{ $pos['key'] }}-select-dropdown"
                                                    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 mb-2"
                                                    onchange="aoqHandleSignatorySelection('{{ $pos['key'] }}')">
                                                <option value="">-- Select a signatory --</option>
                                                @if($preconfigured->isNotEmpty())
                                                    <optgroup label="Pre-configured Signatories">
                                                        @foreach($preconfigured as $config)
                                                            @php
                                                                $optionValue = $config->user_id ?? '';
                                                                $isSelected = (string)$selectedUserId !== '' 
                                                                    ? (string)$selectedUserId === (string)$optionValue
                                                                    : ($selectedSignatoryId && $selectedSignatoryId === $config->id);
                                                            @endphp
                                                            <option value="{{ $optionValue }}"
                                                                    data-signatory-id="{{ $config->id }}"
                                                                    data-prefix="{{ $config->prefix ?? '' }}"
                                                                    data-suffix="{{ $config->suffix ?? '' }}"
                                                                    data-manual-name="{{ $config->manual_name ?? '' }}"
                                                                    data-display-name="{{ $config->display_name }}"
                                                                    data-has-user="{{ $config->user_id ? 'true' : 'false' }}"
                                                                    {{ $isSelected ? 'selected' : '' }}>
                                                                {{ $config->full_name }}
                                                            </option>
                                                        @endforeach
                                                    </optgroup>
                                                @endif
                                                <optgroup label="All BAC Users">
                                                    @foreach($eligibleSignatoryUsers as $user)
                                                        <option value="{{ $user->id }}" 
                                                                data-signatory-id=""
                                                                data-prefix=""
                                                                data-suffix=""
                                                                data-manual-name=""
                                                                data-display-name="{{ $user->name }}"
                                                                data-has-user="true"
                                                                {{ (string)$selectedUserId === (string)$user->id ? 'selected' : '' }}>
                                                            {{ $user->name }} ({{ $user->getRoleNames()->implode(', ') }})
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                            </select>
                                            <input type="hidden" name="signatories[{{ $pos['key'] }}][selected_name]" id="{{ $pos['key'] }}-selected-name" value="{{ $selectedNameValue }}">
                                        </div>

                                        {{-- Manual Entry --}}
                                        <div id="{{ $pos['key'] }}-manual-section" class="{{ $inputMode === 'manual' ? '' : 'hidden' }}">
                                            <input type="text" name="signatories[{{ $pos['key'] }}][name]" 
                                                   placeholder="Enter full name"
                                                   value="{{ $manualValue }}"
                                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 mb-2">
                                        </div>

                                        {{-- Prefix and Suffix --}}
                                        <div class="grid grid-cols-2 gap-2">
                                            <input type="text" name="signatories[{{ $pos['key'] }}][prefix]" 
                                                   placeholder="Prefix (Dr., Engr., etc.)"
                                                   value="{{ $prefixValue }}"
                                                   id="{{ $pos['key'] }}-prefix-field"
                                                   class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                            <input type="text" name="signatories[{{ $pos['key'] }}][suffix]" 
                                                   placeholder="Suffix (Ph.D., CPA, etc.)"
                                                   value="{{ $suffixValue }}"
                                                   id="{{ $pos['key'] }}-suffix-field"
                                                   class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                        </div>
                                    </div>
                                    @endforeach
                                        </div>
                                    </details>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Generate AOQ
                        </button>
                        <button type="button" onclick="closeGenerateAoqModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

