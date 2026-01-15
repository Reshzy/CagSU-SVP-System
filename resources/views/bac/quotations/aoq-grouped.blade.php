@section('title', 'BAC - Abstract of Quotations (Grouped)')

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
            {{ __('Abstract of Quotations (Grouped): ') . $purchaseRequest->pr_number }}
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

            {{-- Info Banner --}}
            <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    <span>This PR has been split into <strong>{{ count($groupsData) }} groups</strong>. Each group will have its own separate AOQ document.</span>
                </div>
            </div>

            {{-- Group Sections --}}
            @foreach($groupsData as $index => $groupInfo)
                @php
                    $group = $groupInfo['group'];
                    $aoqData = $groupInfo['aoqData'];
                    $validation = $groupInfo['validation'];
                    $quotations = $groupInfo['quotations'];
                    $aoqGeneration = $groupInfo['aoqGeneration'];
                    $failedItems = $group->items->where('procurement_status', 'failed');
                @endphp

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg color-black">
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xl font-bold text-white">{{ $group->group_name }}</h3>
                                <p class="text-blue-100 text-sm">Code: {{ $group->group_code }} | {{ $group->items->count() }} items</p>
                            </div>
                            <span class="bg-white bg-opacity-20 text-black px-3 py-1 rounded-full text-sm font-semibold">
                                Group {{ $index + 1 }} of {{ count($groupsData) }}
                            </span>
                        </div>
                    </div>

                    <div class="p-6 space-y-6">
                        {{-- Failed Items Alert (Group-specific) --}}
                        @if($failedItems->isNotEmpty())
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-red-600 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-red-800">Failed Items in This Group</h4>
                                        <ul class="list-disc list-inside text-sm text-red-700 mt-2">
                                            @foreach($failedItems as $failedItem)
                                                <li>{{ $failedItem->item_name }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- AOQ Status for this Group --}}
                        <div>
                            <h4 class="text-lg font-semibold mb-3">AOQ Status</h4>
                            
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
                                        onclick="openGenerateAoqModal({{ $group->id }})"
                                        class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Generate AOQ for {{ $group->group_name }}
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

                        {{-- Existing AOQ Generation --}}
                        @if($aoqGeneration)
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <h4 class="font-semibold text-blue-900 mb-2">Generated AOQ</h4>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm text-blue-800">
                                            <strong>Reference:</strong> {{ $aoqGeneration->aoq_reference_number }}
                                        </p>
                                        <p class="text-sm text-blue-700">
                                            Generated by {{ $aoqGeneration->generatedBy->name }} on {{ $aoqGeneration->created_at->format('M d, Y h:i A') }}
                                        </p>
                                    </div>
                                    <a href="{{ route('bac.item-groups.aoq.download', [$group, $aoqGeneration->id]) }}" 
                                       class="inline-flex items-center px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        Download
                                    </a>
                                </div>
                            </div>
                        @endif

                        {{-- Quotation Comparison Table --}}
                        <div>
                            <h4 class="text-lg font-semibold mb-3">Quotation Evaluation</h4>
                            
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
                                                                }
                                                            @endphp
                                                            <div class="p-2 rounded {{ $bgColor }}">
                                                                <div class="font-semibold text-sm">₱{{ number_format($qi->unit_price, 2) }}</div>
                                                                <div class="text-xs text-gray-600">₱{{ number_format($quoteItem['total_price'], 2) }}</div>
                                                                @if($qi->rank)
                                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $badgeColor }} text-white mt-1">
                                                                        @if($qi->is_withdrawn)
                                                                            Withdrawn
                                                                        @elseif($qi->isDisqualified())
                                                                            Disqualified
                                                                        @elseif($qi->is_winner)
                                                                            Winner
                                                                        @elseif($qi->is_tied)
                                                                            Tied (Rank {{ $qi->rank }})
                                                                        @else
                                                                            Rank {{ $qi->rank }}
                                                                        @endif
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        @else
                                                            <span class="text-gray-400 text-sm">Not Quoted</span>
                                                        @endif
                                                    </td>
                                                @endforeach
                                                
                                                <td class="px-4 py-4">
                                                    @php $winnersArray = array_values($winners); @endphp
                                                    @if($hasTie)
                                                        @php $winner = count($winnersArray) > 0 ? $winnersArray[0] : null; @endphp
                                                        <div class="text-sm">
                                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                                Multiple Winners (Tie)
                                                            </span>
                                                            @if($winner)
                                                                <div class="text-xs text-gray-500 mt-1">
                                                                    Current Winner: {{ $winner['quotation']->supplier->business_name }}
                                                                </div>
                                                            @endif
                                                            <div class="mt-2 flex items-center gap-2">
                                                                @if($winner && $winner['quotation_item']->is_winner)
                                                                    <button type="button"
                                                                            onclick="openWithdrawalModal({{ $winner['quotation_item']->id }}, '{{ addslashes($item->item_name) }}', '{{ addslashes($winner['quotation']->supplier->business_name) }}')"
                                                                            class="text-xs text-orange-600 hover:text-orange-800 underline">
                                                                        Withdraw
                                                                    </button>
                                                                @endif
                                                                <button
                                                                    onclick="openBacOverrideModal({{ $item->id }}, '{{ addslashes($item->item_name) }}', {{ json_encode($itemData['quotes']) }})"
                                                                    class="px-2 py-1 bg-orange-600 text-white text-xs rounded hover:bg-orange-700">
                                                                    Override
                                                                </button>
                                                            </div>
                                                        </div>
                                                    @elseif(count($winnersArray) > 0)
                                                        @if(count($winnersArray) === 1)
                                                            @php $winner = $winnersArray[0]; @endphp
                                                            <div class="text-sm">
                                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                                    ✓ {{ $winner['quotation']->supplier->business_name }}
                                                                </span>
                                                                <div class="text-xs text-gray-500 mt-1">₱{{ number_format($winner['quotation_item']->unit_price, 2) }} / {{ $item->unit_of_measure }}</div>

                                                                @if($winner['quotation_item']->is_winner)
                                                                    <div class="mt-2 flex items-center gap-2">
                                                                        <button type="button"
                                                                                onclick="openWithdrawalModal({{ $winner['quotation_item']->id }}, '{{ addslashes($item->item_name) }}', '{{ addslashes($winner['quotation']->supplier->business_name) }}')"
                                                                                class="text-xs text-orange-600 hover:text-orange-800 underline">
                                                                            Withdraw
                                                                        </button>
                                                                        <button
                                                                            onclick="openBacOverrideModal({{ $item->id }}, '{{ addslashes($item->item_name) }}', {{ json_encode($itemData['quotes']) }})"
                                                                            class="px-2 py-1 bg-orange-600 text-white text-xs rounded hover:bg-orange-700">
                                                                            Override
                                                                        </button>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @else
                                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                                Multiple Winners (Tie)
                                                            </span>
                                                        @endif
                                                    @else
                                                        <span class="text-sm text-gray-500">No Winner</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Back Button --}}
            <div class="flex justify-between">
                <a href="{{ route('bac.quotations.manage', $purchaseRequest) }}" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Quotations
                </a>
            </div>
        </div>
    </div>

    @php
        $signatoryDefaults = $signatoryDefaults ?? [];
        $bacSignatoryOptions = $bacSignatoryOptions ?? [];
        $eligibleSignatoryUsers = $eligibleSignatoryUsers ?? collect();
    @endphp

    {{-- Generate AOQ Modal (Per Group) --}}
    @foreach($groupsData as $groupInfo)
        @php $group = $groupInfo['group']; @endphp
        <div id="generateAoqModal_{{ $group->id }}" class="hidden fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeGenerateAoqModal({{ $group->id }})"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <form action="{{ route('bac.item-groups.aoq.generate', $group) }}" method="POST" id="generateAoqForm_{{ $group->id }}">
                        @csrf
                        
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 color-black">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                        Generate AOQ for {{ $group->group_name }}
                                    </h3>
                                    <div class="mt-4">
                                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                                            <div class="flex items-start space-x-3">
                                                <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <div class="flex-1">
                                                    <h4 class="text-sm font-semibold text-blue-900 mb-1">Signatories Auto-Applied</h4>
                                                    <p class="text-sm text-blue-800">Signatories will be automatically applied from your <a href="{{ route('bac.signatories.index') }}" target="_blank" class="underline font-medium hover:text-blue-900">BAC Signatories Setup</a>.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Generate AOQ
                            </button>
                            <button type="button" onclick="closeGenerateAoqModal({{ $group->id }})" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

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

    {{-- Withdrawal Modal --}}
    <div id="withdrawalModal" class="hidden fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeWithdrawalModal()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="withdrawalForm" method="POST">
                    @csrf
                    
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-orange-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">
                                    Process Supplier Withdrawal
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        Item: <strong id="withdrawal_item_name"></strong><br>
                                        Supplier: <strong id="withdrawal_supplier_name"></strong>
                                    </p>
                                    <div class="mt-4">
                                        <label for="withdrawal_reason" class="block text-sm font-medium text-gray-700">Withdrawal Reason <span class="text-red-500">*</span></label>
                                        <textarea name="withdrawal_reason" id="withdrawal_reason" rows="4" required
                                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500"
                                                  placeholder="Provide a detailed reason for the withdrawal (min. 10 characters)"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-orange-600 text-base font-medium text-white hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Process Withdrawal
                        </button>
                        <button type="button" onclick="closeWithdrawalModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openGenerateAoqModal(groupId) {
            document.getElementById('generateAoqModal_' + groupId).classList.remove('hidden');
        }
        
        function closeGenerateAoqModal(groupId) {
            document.getElementById('generateAoqModal_' + groupId).classList.add('hidden');
        }
        
        function openWithdrawalModal(quotationItemId, itemName, supplierName) {
            const form = document.getElementById('withdrawalForm');
            form.action = '/bac/quotation-items/' + quotationItemId + '/withdraw';
            document.getElementById('withdrawal_item_name').textContent = itemName;
            document.getElementById('withdrawal_supplier_name').textContent = supplierName;
            document.getElementById('withdrawalModal').classList.remove('hidden');
        }
        
        function closeWithdrawalModal() {
            document.getElementById('withdrawalModal').classList.add('hidden');
            document.getElementById('withdrawalForm').reset();
        }

        function openBacOverrideModal(itemId, itemName, quotes) {
            document.getElementById('override_item_id').value = itemId;
            document.getElementById('override_item_name').textContent = itemName;

            const select = document.getElementById('override_winner_select');
            select.innerHTML = '<option value="">-- Select Supplier --</option>';

            quotes.forEach(quote => {
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
            const bacOverrideModal = document.getElementById('bacOverrideModal');
            const withdrawalModal = document.getElementById('withdrawalModal');
            if (event.target === bacOverrideModal) {
                closeBacOverrideModal();
            }
            if (event.target === withdrawalModal) {
                closeWithdrawalModal();
            }
            
            // Check all group modals
            @foreach($groupsData as $groupInfo)
                const modal_{{ $groupInfo['group']->id }} = document.getElementById('generateAoqModal_{{ $groupInfo['group']->id }}');
                if (event.target === modal_{{ $groupInfo['group']->id }}) {
                    closeGenerateAoqModal({{ $groupInfo['group']->id }});
                }
            @endforeach
        }
    </script>
</x-app-layout>
