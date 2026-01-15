{{-- Submitted Quotations for this Group --}}
<div class="bg-white border border-gray-300 rounded-lg p-6">
    <h4 class="font-semibold text-xl text-gray-800 mb-4">Submitted Quotations for {{ $group->group_name }}</h4>

    @if($groupQuotations->isEmpty())
        <div class="text-center py-8 text-gray-500">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <p class="mt-2">No quotations submitted yet for this group</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($groupQuotations as $quotation)
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <div class="bg-gray-50 px-4 py-3 flex items-center justify-between cursor-pointer" onclick="toggleQuotationDetails({{ $quotation->id }})">
                    <div class="flex-1">
                        <div class="flex items-center space-x-3">
                            <h5 class="font-semibold text-lg">{{ $quotation->supplier->business_name ?? 'N/A' }}</h5>

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
                    <h6 class="font-semibold text-gray-800 mb-2">Line Items</h6>
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

{{-- AOQ Section for this Group --}}
@if($groupQuotations->count() > 0)
<div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
    <div class="flex items-start justify-between">
        <div class="flex-1">
            <h4 class="font-semibold text-lg text-gray-800 mb-2">Abstract of Quotations (AOQ) - {{ $group->group_name }}</h4>
            <p class="text-sm text-gray-600 mb-2">
                Review quotations, resolve ties, and generate the official Abstract of Quotations document for this group.
            </p>
        </div>
        <div class="flex flex-col space-y-2 ml-4">
            <a href="{{ route('bac.quotations.aoq', ['purchaseRequest' => $purchaseRequest, 'group' => $group->id]) }}"
               class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-md hover:bg-purple-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                View / Generate AOQ
            </a>
        </div>
    </div>

    {{-- Quick AOQ Comparison Table --}}
    <div class="mt-4 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 border text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th rowspan="2" class="px-3 py-2 text-left text-xs font-semibold text-gray-700 border-r">Item Description</th>
                    <th rowspan="2" class="px-3 py-2 text-center text-xs font-semibold text-gray-700 border-r">Unit</th>
                    <th rowspan="2" class="px-3 py-2 text-center text-xs font-semibold text-gray-700 border-r">Qty</th>
                    <th rowspan="2" class="px-3 py-2 text-right text-xs font-semibold text-gray-700 border-r">ABC</th>
                    <th colspan="{{ $groupQuotations->count() }}" class="px-3 py-2 text-center text-xs font-semibold text-gray-700">Supplier Quotations (Unit Price)</th>
                </tr>
                <tr>
                    @foreach($groupQuotations as $quotation)
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
                @foreach($group->items as $prItem)
                <tr>
                    <td class="px-3 py-2 border-r">{{ $prItem->item_name }}</td>
                    <td class="px-3 py-2 text-center border-r">{{ $prItem->unit_of_measure }}</td>
                    <td class="px-3 py-2 text-center border-r">{{ $prItem->quantity_requested }}</td>
                    <td class="px-3 py-2 text-right font-mono border-r">₱{{ number_format((float)$prItem->estimated_unit_cost, 2) }}</td>

                    @php
                        $itemQuotations = [];
                        foreach($groupQuotations as $q) {
                            $quotItem = $q->quotationItems->firstWhere('purchase_request_item_id', $prItem->id);
                            $itemQuotations[] = $quotItem;
                        }
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
                    <td class="px-3 py-3 text-right font-mono border-r">₱{{ number_format($group->calculateTotalCost(), 2) }}</td>
                    @foreach($groupQuotations as $quotation)
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
