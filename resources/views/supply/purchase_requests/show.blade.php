@section('title', 'PR Details - ' . $purchaseRequest->pr_number)

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('supply.purchase-requests.index') }}" class="text-gray-600 hover:text-gray-900">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ $purchaseRequest->pr_number }}</h2>
                
                @php
                    $statusColors = [
                        'submitted' => 'bg-blue-100 text-blue-800',
                        'supply_office_review' => 'bg-yellow-100 text-yellow-800',
                        'budget_office_review' => 'bg-purple-100 text-purple-800',
                        'bac_evaluation' => 'bg-orange-100 text-orange-800',
                        'bac_approved' => 'bg-green-100 text-green-800',
                        'rejected' => 'bg-red-100 text-red-800',
                        'cancelled' => 'bg-gray-100 text-gray-800',
                        'returned_by_supply' => 'bg-yellow-100 text-yellow-800',
                    ];
                    $statusColor = $statusColors[$purchaseRequest->status] ?? 'bg-gray-100 text-gray-800';
                    $statusDisplay = $purchaseRequest->status === 'rejected' ? 'Deferred' : str_replace('_', ' ', Str::title($purchaseRequest->status));
                @endphp
                <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $statusColor }}">
                    {{ $statusDisplay }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Basic Information Card -->
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Purchase Request Information</h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-600">Requester</label>
                                    <p class="text-gray-900 font-medium">{{ $purchaseRequest->requester?->name }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-600">Department</label>
                                    <p class="text-gray-900 font-medium">{{ $purchaseRequest->department?->name }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-600">Estimated Total</label>
                                    <p class="text-gray-900 font-semibold text-lg">₱{{ number_format($purchaseRequest->estimated_total, 2) }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-600">Date Submitted</label>
                                    <p class="text-gray-900">{{ $purchaseRequest->submitted_at?->format('F d, Y h:i A') ?? $purchaseRequest->created_at->format('F d, Y h:i A') }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-600">Date Needed</label>
                                    <p class="text-gray-900">{{ $purchaseRequest->date_needed?->format('F d, Y') ?? 'Not specified' }}</p>
                                </div>
                            </div>

                            <div class="pt-4 border-t">
                                <label class="text-sm font-medium text-gray-600">Purpose</label>
                                <p class="text-gray-900 mt-1">{{ $purchaseRequest->purpose }}</p>
                            </div>

                            @if($purchaseRequest->justification)
                            <div>
                                <label class="text-sm font-medium text-gray-600">Justification</label>
                                <p class="text-gray-900 mt-1">{{ $purchaseRequest->justification }}</p>
                            </div>
                            @endif

                            @if($purchaseRequest->funding_source)
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t">
                                <div>
                                    <label class="text-sm font-medium text-gray-600">Funding Source</label>
                                    <p class="text-gray-900">{{ $purchaseRequest->funding_source }}</p>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Items Card -->
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden" x-data="supplyLotManager({{ $purchaseRequest->id }}, {{ json_encode(in_array($purchaseRequest->status, ['submitted', 'supply_office_review'])) }})">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900">Requested Items ({{ $purchaseRequest->items->count() }})</h3>
                            @if(in_array($purchaseRequest->status, ['submitted', 'supply_office_review']))
                            <button type="button" @click="openCreateLot()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium bg-indigo-600 text-white rounded hover:bg-indigo-700">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                Create Lot
                            </button>
                            @endif
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Description</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Cost</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                        @if(in_array($purchaseRequest->status, ['submitted', 'supply_office_review']))
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($purchaseRequest->items->load('lotChildren') as $item)
                                        @if($item->isLotChild()) @continue @endif
                                        @if($item->isLotHeader())
                                        <tr class="bg-indigo-50">
                                            <td class="px-6 py-3 font-bold text-indigo-700 uppercase text-xs">lot</td>
                                            <td class="px-6 py-3 font-bold text-indigo-800 uppercase">{{ strtoupper($item->lot_name ?? $item->item_name) }}</td>
                                            <td class="px-6 py-3 text-right text-gray-700">1</td>
                                            <td class="px-6 py-3 text-right text-gray-700">₱{{ number_format($item->estimated_unit_cost ?? 0, 2) }}</td>
                                            <td class="px-6 py-3 text-right font-semibold text-indigo-700">₱{{ number_format($item->estimated_total_cost ?? 0, 2) }}</td>
                                            @if(in_array($purchaseRequest->status, ['submitted', 'supply_office_review']))
                                            <td class="px-6 py-3 text-center">
                                                <div class="flex items-center justify-center gap-2">
                                                    <button type="button" @click="openEditLot({{ $item->id }}, '{{ addslashes($item->lot_name ?? $item->item_name) }}', {{ json_encode($item->lotChildren->pluck('id')) }})" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">Edit</button>
                                                    <button type="button" @click="destroyLot({{ $item->id }}, '{{ addslashes($item->lot_name ?? $item->item_name) }}')" class="text-xs text-orange-600 hover:text-orange-800 font-medium">Ungroup</button>
                                                </div>
                                            </td>
                                            @endif
                                        </tr>
                                        @foreach($item->lotChildren as $child)
                                        <tr class="bg-indigo-50/30">
                                            <td class="px-6 py-2 text-gray-400"></td>
                                            <td class="px-6 py-2 text-gray-600 pl-10">
                                                <span class="text-indigo-400 mr-1">↳</span>
                                                {{ $child->quantity_requested }} {{ $child->unit_of_measure }}, {{ $child->item_name }}
                                            </td>
                                            <td class="px-6 py-2"></td>
                                            <td class="px-6 py-2"></td>
                                            <td class="px-6 py-2"></td>
                                            @if(in_array($purchaseRequest->status, ['submitted', 'supply_office_review']))
                                            <td class="px-6 py-2"></td>
                                            @endif
                                        </tr>
                                        @endforeach
                                        @else
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 text-gray-900">{{ $item->unit_of_measure }}</td>
                                            <td class="px-6 py-4">
                                                <div class="font-medium text-gray-900">{{ $item->item_name }}</div>
                                                @if($item->item_code)
                                                <div class="text-xs text-gray-500">Code: {{ $item->item_code }}</div>
                                                @endif
                                                @if($item->detailed_specifications)
                                                <div class="text-xs text-gray-500 mt-0.5">{{ Str::limit($item->detailed_specifications, 60) }}</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-right font-semibold text-gray-900">{{ number_format($item->quantity_requested ?? 0) }}</td>
                                            <td class="px-6 py-4 text-right text-gray-900">₱{{ number_format($item->estimated_unit_cost ?? 0, 2) }}</td>
                                            <td class="px-6 py-4 text-right font-medium text-gray-900">₱{{ number_format($item->estimated_total_cost ?? 0, 2) }}</td>
                                            @if(in_array($purchaseRequest->status, ['submitted', 'supply_office_review']))
                                            <td class="px-6 py-4 text-center">
                                                {{-- Individual items have no direct action here; they can be added to a lot via Create Lot --}}
                                            </td>
                                            @endif
                                        </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-gray-50">
                                    <tr>
                                        <td colspan="{{ in_array($purchaseRequest->status, ['submitted', 'supply_office_review']) ? 4 : 4 }}" class="px-6 py-4 text-sm font-semibold text-gray-900 text-right">Total:</td>
                                        <td class="px-6 py-4 text-sm font-bold text-gray-900 text-right">₱{{ number_format($purchaseRequest->estimated_total, 2) }}</td>
                                        @if(in_array($purchaseRequest->status, ['submitted', 'supply_office_review']))
                                        <td></td>
                                        @endif
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Lot Management Modal (Supply Officer) -->
                        <div x-show="showLotModal" x-cloak
                             class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center"
                             @click.self="closeLotModal">
                            <div class="relative p-5 border w-[32rem] shadow-lg rounded-md bg-white max-h-[90vh] overflow-y-auto">
                                <h3 class="text-lg font-bold text-gray-900 mb-1" x-text="editingLotId ? 'Edit Lot' : 'Create Lot'"></h3>
                                <p class="text-xs text-gray-500 mb-4">Combine standalone items into a single lot entry.</p>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Lot Name <span class="text-red-500">*</span></label>
                                    <input type="text" x-model="lotForm.name" placeholder="e.g. Painting Works" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Items to Include</label>
                                    <div class="space-y-2 max-h-64 overflow-y-auto">
                                        <template x-for="item in lotCandidates" :key="item.id">
                                            <label class="flex items-start gap-2 p-2 rounded border cursor-pointer hover:bg-gray-50"
                                                   :class="lotForm.selectedIds.includes(item.id) ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200'">
                                                <input type="checkbox" :checked="lotForm.selectedIds.includes(item.id)" @change="toggleLotItem(item.id)" class="mt-0.5 rounded border-gray-300 text-indigo-600" />
                                                <div>
                                                    <div class="text-sm font-medium text-gray-800" x-text="item.name"></div>
                                                    <div class="text-xs text-gray-500" x-text="item.qty + ' ' + item.unit + ' · ₱' + formatNum(item.total)"></div>
                                                </div>
                                            </label>
                                        </template>
                                    </div>
                                </div>

                                <div class="bg-gray-50 rounded p-3 mb-4 text-sm flex justify-between">
                                    <span class="text-gray-600">Items: <span x-text="lotForm.selectedIds.length" class="font-medium"></span></span>
                                    <span class="font-semibold text-indigo-700">Combined: ₱<span x-text="formatNum(lotModalTotal)"></span></span>
                                </div>

                                <div class="flex gap-2 justify-end">
                                    <button type="button" @click="closeLotModal" class="px-4 py-2 bg-gray-500 hover:bg-gray-700 text-white font-bold rounded text-sm">Cancel</button>
                                    <button type="button" @click="saveLot" :disabled="lotForm.selectedIds.length < 2 || !lotForm.name.trim() || saving" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded text-sm disabled:opacity-50" x-text="saving ? 'Saving...' : (editingLotId ? 'Update Lot' : 'Create Lot')"></button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Item Groups & Quotations Card -->
                    @if($purchaseRequest->itemGroups->count() > 0)
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Item Groups & Quotations ({{ $purchaseRequest->itemGroups->count() }} groups)</h3>
                        </div>
                        <div class="p-6 space-y-4">
                            @foreach($purchaseRequest->itemGroups as $group)
                            <div class="border border-gray-200 rounded-lg p-4 hover:border-gray-300 transition">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-2">
                                            <h4 class="text-base font-semibold text-gray-900">{{ $group->group_code }}: {{ $group->group_name }}</h4>
                                            @if($group->isReadyForPo())
                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                                    ✓ AOQ Generated
                                                </span>
                                            @else
                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">
                                                    ⏳ Awaiting AOQ
                                                </span>
                                            @endif
                                        </div>
                                        
                                        <div class="grid grid-cols-2 gap-4 text-sm text-gray-600 mb-3">
                                            <div>
                                                <span class="font-medium">Items:</span> {{ $group->items->count() }}
                                            </div>
                                            <div>
                                                <span class="font-medium">Est. Total:</span> ₱{{ number_format($group->calculateTotalCost(), 2) }}
                                            </div>
                                        </div>

                                        @if($group->aoqGeneration)
                                        <div class="text-sm text-gray-600 mb-2">
                                            <span class="font-medium">AOQ:</span> {{ $group->aoqGeneration->aoq_reference_number }}
                                        </div>
                                        @endif

                                        @php
                                            $winningQuotation = $group->getWinningQuotation();
                                        @endphp
                                        @if($winningQuotation)
                                        <div class="text-sm text-gray-600 mb-2">
                                            <span class="font-medium">Supplier:</span> {{ $winningQuotation->supplier->business_name }} <span class="text-green-600 font-semibold">(Winner)</span>
                                        </div>
                                        @endif

                                        @if($group->hasExistingPo())
                                        <div class="text-sm mb-2">
                                            <span class="font-medium text-gray-600">PO:</span>
                                            @foreach($group->purchaseOrders as $po)
                                                <a href="{{ route('supply.purchase-orders.show', $po) }}" class="text-cagsu-maroon hover:text-cagsu-orange font-medium">
                                                    {{ $po->po_number }}
                                                </a>
                                            @endforeach
                                        </div>
                                        @else
                                        <div class="text-sm text-gray-500">
                                            <span class="font-medium">PO Status:</span> Pending
                                        </div>
                                        @endif
                                    </div>

                                    <div class="ml-4">
                                        @if($group->hasExistingPo())
                                            <div class="flex gap-2">
                                                <a href="{{ route('supply.purchase-orders.show', $group->purchaseOrders->first()) }}" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition text-sm font-medium">
                                                    View PO →
                                                </a>
                                                <a href="{{ route('supply.purchase-orders.edit', $group->purchaseOrders->first()) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                    Edit
                                                </a>
                                            </div>
                                        @elseif($group->isReadyForPo() && in_array($purchaseRequest->status, ['bac_evaluation', 'bac_approved', 'partial_po_generation']))
                                            <a href="{{ route('supply.purchase-orders.preview', ['purchaseRequest' => $purchaseRequest, 'group' => $group->id]) }}" class="inline-flex items-center px-4 py-2 bg-cagsu-maroon text-white rounded-lg hover:bg-cagsu-orange transition text-sm font-medium">
                                                Create PO →
                                            </a>
                                        @else
                                            <span class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-400 rounded-lg text-sm font-medium cursor-not-allowed">
                                                Not Ready
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Documents Card -->
                    @if($purchaseRequest->documents->count() > 0)
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Attachments</h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-2">
                                @foreach($purchaseRequest->documents as $doc)
                                <a href="{{ route('files.show', $doc) }}" target="_blank" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <span class="text-sm font-medium text-cagsu-maroon hover:text-cagsu-orange">{{ $doc->file_name }}</span>
                                </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Notes & Remarks -->
                    @if($purchaseRequest->current_step_notes || $purchaseRequest->return_remarks || $purchaseRequest->rejection_reason)
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Notes & Remarks</h3>
                        </div>
                        <div class="p-6 space-y-3">
                            @if($purchaseRequest->current_step_notes)
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <label class="text-sm font-medium text-blue-800">Current Step Notes</label>
                                <p class="text-sm text-blue-900 mt-1">{{ $purchaseRequest->current_step_notes }}</p>
                            </div>
                            @endif

                            @if($purchaseRequest->return_remarks)
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <label class="text-sm font-medium text-yellow-800">Return Remarks</label>
                                <p class="text-sm text-yellow-900 mt-1">{{ $purchaseRequest->return_remarks }}</p>
                                @if($purchaseRequest->returnedBy)
                                <p class="text-xs text-yellow-700 mt-2">Returned by: {{ $purchaseRequest->returnedBy->name }} on {{ $purchaseRequest->returned_at?->format('F d, Y h:i A') }}</p>
                                @endif
                            </div>
                            @endif

                            @if($purchaseRequest->rejection_reason)
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                <label class="text-sm font-medium text-red-800">Deferral Reason</label>
                                <p class="text-sm text-red-900 mt-1">{{ $purchaseRequest->rejection_reason }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Replacement Information -->
                    @if($purchaseRequest->replacesPr)
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Replacement Information</h3>
                        </div>
                        <div class="p-6">
                            <p class="text-sm text-gray-600">This PR replaces:</p>
                            <a href="{{ route('supply.purchase-requests.show', $purchaseRequest->replacesPr) }}" class="text-cagsu-maroon hover:text-cagsu-orange font-medium">
                                {{ $purchaseRequest->replacesPr->pr_number }}
                            </a>
                            <p class="text-xs text-gray-500 mt-1">Submitted by {{ $purchaseRequest->replacesPr->requester?->name }}</p>
                        </div>
                    </div>
                    @endif
                    
                </div>

                <!-- Action Sidebar -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden sticky top-[calc(var(--app-sticky-header-offset)+0.75rem)]">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Actions</h3>
                        </div>
                        <div class="p-6 space-y-4">
                            
                            <!-- Start Review -->
                            @if($purchaseRequest->status === 'submitted')
                            <form action="{{ route('supply.purchase-requests.status', $purchaseRequest) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="action" value="start_review">
                                <button type="submit" class="w-full px-4 py-3 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition font-medium">
                                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                    Start Review
                                </button>
                            </form>
                            @endif

                            <!-- Activate (Send to Budget) -->
                            @if(in_array($purchaseRequest->status, ['submitted', 'supply_office_review']))
                            <div x-data="{ showNotes: false }">
                                <button @click="showNotes = !showNotes" class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium">
                                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Activate & Send to Budget
                                </button>
                                
                                <div x-show="showNotes" x-cloak class="mt-2">
                                    <form action="{{ route('supply.purchase-requests.status', $purchaseRequest) }}" method="POST" class="space-y-3">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="action" value="activate">
                                        <textarea name="notes" rows="3" placeholder="Optional notes..." class="w-full border-gray-300 rounded-lg focus:border-cagsu-maroon focus:ring-cagsu-maroon"></textarea>
                                        <div class="flex gap-2">
                                            <button type="submit" class="flex-1 px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium">
                                                Confirm
                                            </button>
                                            <button type="button" @click="showNotes = false" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">
                                                Cancel
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            @endif

                            <!-- Return to Department -->
                            @if(in_array($purchaseRequest->status, ['submitted', 'supply_office_review']))
                            <div x-data="{ showReturn: false }">
                                <button @click="showReturn = !showReturn" class="w-full px-4 py-3 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition font-medium">
                                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                    </svg>
                                    Return to Department
                                </button>
                                
                                <div x-show="showReturn" x-cloak class="mt-2">
                                    <form action="{{ route('supply.purchase-requests.status', $purchaseRequest) }}" method="POST" class="space-y-3">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="action" value="return">
                                        <textarea name="return_remarks" rows="3" placeholder="Reason for return (required)..." class="w-full border-gray-300 rounded-lg focus:border-cagsu-maroon focus:ring-cagsu-maroon" required></textarea>
                                        <div class="flex gap-2">
                                            <button type="submit" class="flex-1 px-3 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 text-sm font-medium">
                                                Return PR
                                            </button>
                                            <button type="button" @click="showReturn = false" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">
                                                Cancel
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            @endif

                            <!-- Defer -->
                            @if(in_array($purchaseRequest->status, ['submitted', 'supply_office_review']))
                            <div x-data="{ showDefer: false }">
                                <button @click="showDefer = !showDefer" class="w-full px-4 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium">
                                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    Defer PR
                                </button>
                                
                                <div x-show="showDefer" x-cloak class="mt-2">
                                    <form action="{{ route('supply.purchase-requests.status', $purchaseRequest) }}" method="POST" class="space-y-3" onsubmit="return confirm('Are you sure you want to defer this PR? This action cannot be undone.');">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="action" value="reject">
                                        <textarea name="rejection_reason" rows="3" placeholder="Deferral reason (required)..." class="w-full border-gray-300 rounded-lg focus:border-cagsu-maroon focus:ring-cagsu-maroon" required></textarea>
                                        <div class="flex gap-2">
                                            <button type="submit" class="flex-1 px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-medium">
                                                Confirm Deferral
                                            </button>
                                            <button type="button" @click="showDefer = false" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">
                                                Cancel
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            @endif

                            <!-- Create Purchase Order -->
                            @if(in_array($purchaseRequest->status, ['bac_evaluation', 'bac_approved']))
                                @if($purchaseRequest->itemGroups->count() > 0)
                                    <!-- Dropdown for grouped PRs -->
                                    <div x-data="{ open: false }" class="relative">
                                        <button @click="open = !open" class="w-full px-4 py-3 bg-cagsu-maroon text-white rounded-lg hover:bg-cagsu-orange transition font-medium flex items-center justify-between">
                                            <span class="flex items-center">
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                                Create Purchase Order
                                            </span>
                                            <svg class="w-4 h-4 ml-2 transition-transform" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </button>
                                        
                                        <div x-show="open" @click.away="open = false" x-cloak class="absolute z-10 w-full mt-2 bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden">
                                            @foreach($purchaseRequest->itemGroups as $group)
                                            <div class="border-b border-gray-100 last:border-b-0">
                                                <div class="p-3 hover:bg-gray-50">
                                                    <div class="flex items-start justify-between">
                                                        <div class="flex-1 min-w-0">
                                                            <div class="flex items-center gap-2 mb-1">
                                                                <span class="text-sm font-semibold text-gray-900">{{ $group->group_code }}: {{ $group->group_name }}</span>
                                                            </div>
                                                            
                                                            @if($group->isReadyForPo())
                                                                <div class="flex items-center gap-1 text-xs text-green-600 mb-1">
                                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                                    </svg>
                                                                    <span>AOQ Generated</span>
                                                                </div>
                                                            @else
                                                                <div class="flex items-center gap-1 text-xs text-yellow-600 mb-1">
                                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                                                    </svg>
                                                                    <span>Awaiting AOQ</span>
                                                                </div>
                                                            @endif

                                                            @php
                                                                $winningQuotation = $group->getWinningQuotation();
                                                            @endphp
                                                            @if($winningQuotation)
                                                                <div class="text-xs text-gray-600 truncate">
                                                                    Supplier: {{ $winningQuotation->supplier->business_name }}
                                                                </div>
                                                            @endif

                                                            @if($group->hasExistingPo())
                                                                <div class="flex items-center gap-1 text-xs text-blue-600 mt-1">
                                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                                    </svg>
                                                                    <span>PO: {{ $group->purchaseOrders->first()->po_number }}</span>
                                                                </div>
                                                            @endif
                                                        </div>

                                                        <div class="ml-2 flex-shrink-0">
                                                            @if($group->hasExistingPo())
                                                                <div class="flex gap-1">
                                                                    <a href="{{ route('supply.purchase-orders.show', $group->purchaseOrders->first()) }}" class="inline-flex items-center px-3 py-1 bg-gray-100 text-gray-700 rounded text-xs font-medium hover:bg-gray-200">
                                                                        View PO
                                                                    </a>
                                                                    <a href="{{ route('supply.purchase-orders.edit', $group->purchaseOrders->first()) }}" class="inline-flex items-center px-2 py-1 bg-blue-600 text-white rounded text-xs font-medium hover:bg-blue-700" title="Edit PO">
                                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                                        </svg>
                                                                    </a>
                                                                </div>
                                                            @elseif($group->isReadyForPo())
                                                                <a href="{{ route('supply.purchase-orders.preview', ['purchaseRequest' => $purchaseRequest, 'group' => $group->id]) }}" class="inline-flex items-center px-3 py-1 bg-cagsu-maroon text-white rounded text-xs font-medium hover:bg-cagsu-orange">
                                                                    Create PO
                                                                </a>
                                                            @else
                                                                <span class="inline-flex items-center px-3 py-1 bg-gray-100 text-gray-400 rounded text-xs font-medium cursor-not-allowed">
                                                                    Not Ready
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <!-- Single button for non-grouped PRs -->
                                    <a href="{{ route('supply.purchase-orders.preview', $purchaseRequest) }}" class="block w-full px-4 py-3 bg-cagsu-maroon text-white rounded-lg hover:bg-cagsu-orange transition font-medium text-center">
                                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        Create Purchase Order
                                    </a>
                                @endif
                            @endif

                            <!-- Export PR -->
                            <div class="pt-4 border-t">
                                <a href="{{ route('supply.purchase-requests.export', $purchaseRequest) }}" class="w-full inline-flex items-center justify-center px-4 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition font-medium">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Export PR (Excel)
                                </a>
                            </div>

                            <!-- Quick Navigation -->
                            <div class="pt-2">
                                <a href="{{ route('supply.purchase-orders.index') }}" class="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition font-medium">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                    View All Purchase Orders
                                </a>
                            </div>

                            <!-- Info Section -->
                            <div class="pt-4 border-t space-y-3">
                                <div>
                                    <label class="text-xs font-medium text-gray-600">Current Handler</label>
                                    <p class="text-sm text-gray-900">{{ $purchaseRequest->currentHandler?->name ?? 'Unassigned' }}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-600">Last Updated</label>
                                    <p class="text-sm text-gray-900">{{ $purchaseRequest->status_updated_at?->diffForHumans() ?? $purchaseRequest->updated_at->diffForHumans() }}</p>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>

            <!-- Activity Timeline -->
            <div class="mt-6">
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="bg-gradient-to-r from-cagsu-maroon to-cagsu-orange px-6 py-4">
                        <h3 class="text-lg font-bold text-white">Activity Timeline</h3>
                    </div>
                    <div class="p-6">
                        <x-pr-timeline :activities="$purchaseRequest->activities" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function supplyLotManager(prId, canManage) {
            return {
                prId,
                canManage,
                showLotModal: false,
                editingLotId: null,
                saving: false,
                lotForm: { name: '', selectedIds: [] },
                allItems: @php
                    $supplyLotItems = $purchaseRequest->items
                        ->filter(function ($i) {
                            return ! $i->isLotChild();
                        })
                        ->map(function ($i) {
                            return [
                                'id' => $i->id,
                                'name' => $i->item_name,
                                'qty' => $i->quantity_requested,
                                'unit' => $i->unit_of_measure,
                                'total' => $i->estimated_total_cost,
                                'isLot' => $i->isLotHeader(),
                                'childIds' => $i->isLotHeader() ? $i->lotChildren->pluck('id')->toArray() : [],
                            ];
                        })
                        ->values()
                        ->toArray();
                    echo json_encode($supplyLotItems, JSON_UNESCAPED_UNICODE);
                @endphp,

                get lotCandidates() {
                    const base = this.allItems.filter(i => !i.isLot);

                    if (this.editingLotId) {
                        const currentLot = this.allItems.find(i => i.id === this.editingLotId && i.isLot);
                        return currentLot ? [...base, currentLot] : base;
                    }

                    return base;
                },

                get lotModalTotal() {
                    return this.allItems
                        .filter(i => this.lotForm.selectedIds.includes(i.id))
                        .reduce((sum, i) => sum + parseFloat(i.total || 0), 0);
                },

                openCreateLot() {
                    this.editingLotId = null;
                    this.lotForm = { name: '', selectedIds: [] };
                    this.showLotModal = true;
                },

                openEditLot(lotId, lotName, childIds) {
                    this.editingLotId = lotId;
                    this.lotForm = { name: lotName, selectedIds: childIds };
                    this.showLotModal = true;
                },

                closeLotModal() {
                    this.showLotModal = false;
                    this.editingLotId = null;
                    this.lotForm = { name: '', selectedIds: [] };
                },

                toggleLotItem(id) {
                    const idx = this.lotForm.selectedIds.indexOf(id);
                    if (idx > -1) { this.lotForm.selectedIds.splice(idx, 1); } else { this.lotForm.selectedIds.push(id); }
                },

                async saveLot() {
                    if (this.saving) return;
                    this.saving = true;
                    try {
                        const url = this.editingLotId
                            ? `/supply/purchase-requests/${this.prId}/lots/${this.editingLotId}`
                            : `/supply/purchase-requests/${this.prId}/lots`;
                        const method = this.editingLotId ? 'PUT' : 'POST';
                        const resp = await fetch(url, {
                            method,
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: JSON.stringify({ lot_name: this.lotForm.name, item_ids: this.lotForm.selectedIds }),
                        });
                        if (resp.ok) { window.location.reload(); } else { alert('Error saving lot. Please try again.'); }
                    } finally {
                        this.saving = false;
                    }
                },

                async destroyLot(lotId, lotName) {
                    if (!confirm(`Remove lot "${lotName}"? Items will become individual entries again.`)) return;
                    const resp = await fetch(`/supply/purchase-requests/${this.prId}/lots/${lotId}`, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    });
                    if (resp.ok) { window.location.reload(); } else { alert('Error removing lot.'); }
                },

                formatNum(n) {
                    return parseFloat(n || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                },
            };
        }
    </script>
    @endpush
</x-app-layout>

