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
                                @if($purchaseRequest->budget_code)
                                <div>
                                    <label class="text-sm font-medium text-gray-600">Budget Code</label>
                                    <p class="text-gray-900">{{ $purchaseRequest->budget_code }}</p>
                                </div>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Items Card -->
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Requested Items ({{ $purchaseRequest->items->count() }})</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Specifications</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Cost</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($purchaseRequest->items as $item)
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $item->item_name }}</div>
                                            @if($item->item_code)
                                            <div class="text-xs text-gray-500">Code: {{ $item->item_code }}</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">{{ Str::limit($item->detailed_specifications ?? 'N/A', 50) }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">{{ $item->unit_of_measure }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-900 text-right font-semibold">{{ number_format($item->quantity_requested ?? 0) }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-900 text-right">₱{{ number_format($item->estimated_unit_cost ?? 0, 2) }}</td>
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900 text-right">₱{{ number_format($item->estimated_total_cost ?? 0, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-gray-50">
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-sm font-semibold text-gray-900 text-right">Total:</td>
                                        <td class="px-6 py-4 text-sm font-bold text-gray-900 text-right">₱{{ number_format($purchaseRequest->estimated_total, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
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
                                            <a href="{{ route('supply.purchase-orders.show', $group->purchaseOrders->first()) }}" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition text-sm font-medium">
                                                View PO →
                                            </a>
                                        @elseif($group->isReadyForPo() && in_array($purchaseRequest->status, ['bac_evaluation', 'bac_approved']))
                                            <a href="{{ route('supply.purchase-orders.create', ['purchaseRequest' => $purchaseRequest, 'group' => $group->id]) }}" class="inline-flex items-center px-4 py-2 bg-cagsu-maroon text-white rounded-lg hover:bg-cagsu-orange transition text-sm font-medium">
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
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden sticky top-6">
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
                                                                <a href="{{ route('supply.purchase-orders.show', $group->purchaseOrders->first()) }}" class="inline-flex items-center px-3 py-1 bg-gray-100 text-gray-700 rounded text-xs font-medium hover:bg-gray-200">
                                                                    View PO
                                                                </a>
                                                            @elseif($group->isReadyForPo())
                                                                <a href="{{ route('supply.purchase-orders.create', ['purchaseRequest' => $purchaseRequest, 'group' => $group->id]) }}" class="inline-flex items-center px-3 py-1 bg-cagsu-maroon text-white rounded text-xs font-medium hover:bg-cagsu-orange">
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
                                    <a href="{{ route('supply.purchase-orders.create', $purchaseRequest) }}" class="block w-full px-4 py-3 bg-cagsu-maroon text-white rounded-lg hover:bg-cagsu-orange transition font-medium text-center">
                                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        Create Purchase Order
                                    </a>
                                @endif
                            @endif

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
</x-app-layout>

