@section('title', 'Purchase Request Details')

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-2xl text-gray-800 leading-tight">Purchase Request Details</h2>
                <p class="text-sm text-gray-600 mt-1">{{ $purchaseRequest->pr_number }}</p>
            </div>
            <a href="{{ route('purchase-requests.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to List
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Status Alert for Returned PRs -->
            @if($purchaseRequest->status === 'returned_by_supply')
            <div class="bg-orange-50 border-l-4 border-orange-500 rounded-lg shadow-sm">
                <div class="p-6">
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-orange-500 mr-3 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-orange-900 mb-2">This PR was returned by the Supply Office</h3>
                            @if($purchaseRequest->return_remarks)
                            <div class="bg-white border border-orange-200 rounded-lg p-4 mb-4">
                                <p class="text-sm font-semibold text-orange-900 mb-2">Supply Officer's Remarks:</p>
                                <p class="text-sm text-gray-900">{{ $purchaseRequest->return_remarks }}</p>
                                @if($purchaseRequest->returnedBy)
                                <p class="text-xs text-gray-600 mt-2">
                                    Returned by {{ $purchaseRequest->returnedBy->name }} on {{ $purchaseRequest->returned_at?->format('M d, Y h:i A') }}
                                </p>
                                @endif
                            </div>
                            @endif
                            <a href="{{ route('purchase-requests.replacement.create', $purchaseRequest) }}" class="inline-flex items-center px-4 py-2 bg-cagsu-maroon text-white rounded-lg hover:bg-cagsu-orange transition font-medium">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Create Replacement PR
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Left Column: PR Details & Items -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- PR Information Card -->
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                        <div class="bg-gradient-to-r from-cagsu-maroon to-cagsu-orange px-6 py-4">
                            <h3 class="text-lg font-bold text-white">Purchase Request Information</h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="text-sm font-medium text-gray-600">PR Number</label>
                                    <p class="text-gray-900 font-mono font-bold">{{ $purchaseRequest->pr_number }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-600">Status</label>
                                    @php
                                        $statusColors = [
                                            'draft' => 'bg-gray-100 text-gray-800',
                                            'submitted' => 'bg-blue-100 text-blue-800',
                                            'supply_office_review' => 'bg-yellow-100 text-yellow-800',
                                            'budget_office_review' => 'bg-purple-100 text-purple-800',
                                            'returned_by_supply' => 'bg-orange-100 text-orange-800',
                                            'bac_evaluation' => 'bg-orange-100 text-orange-800',
                                            'bac_approved' => 'bg-green-100 text-green-800',
                                            'completed' => 'bg-green-100 text-green-800',
                                            'rejected' => 'bg-red-100 text-red-800',
                                            'cancelled' => 'bg-gray-100 text-gray-800',
                                        ];
                                        $statusColor = $statusColors[$purchaseRequest->status] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <p>
                                        <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full {{ $statusColor }}">
                                            {{ str_replace('_', ' ', Str::title($purchaseRequest->status)) }}
                                        </span>
                                    </p>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="text-sm font-medium text-gray-600">Purpose</label>
                                    <p class="text-gray-900">{{ $purchaseRequest->purpose }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-600">Department</label>
                                    <p class="text-gray-900">{{ $purchaseRequest->department->name }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-600">Requested By</label>
                                    <p class="text-gray-900">{{ $purchaseRequest->requester->name }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-600">Date Needed</label>
                                    <p class="text-gray-900">{{ $purchaseRequest->date_needed?->format('F d, Y') ?? 'Not specified' }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-600">Funding Source</label>
                                    <p class="text-gray-900 capitalize">{{ str_replace('_', ' ', $purchaseRequest->funding_source) }}</p>
                                </div>
                                @if($purchaseRequest->estimated_total)
                                <div>
                                    <label class="text-sm font-medium text-gray-600">Estimated Total</label>
                                    <p class="text-gray-900 font-bold">₱{{ number_format($purchaseRequest->estimated_total, 2) }}</p>
                                </div>
                                @endif
                                <div>
                                    <label class="text-sm font-medium text-gray-600">Created</label>
                                    <p class="text-gray-900">{{ $purchaseRequest->created_at->format('M d, Y h:i A') }}</p>
                                </div>
                            </div>

                            @if($purchaseRequest->replacesPr)
                            <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-blue-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-semibold text-blue-900">This is a replacement PR</p>
                                        <p class="text-sm text-blue-800 mt-1">
                                            Replaces: <span class="font-mono font-bold">{{ $purchaseRequest->replacesPr->pr_number }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            @endif

                            @if($purchaseRequest->replacedByPr)
                            <div class="mt-6 p-4 bg-purple-50 border border-purple-200 rounded-lg">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-purple-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-semibold text-purple-900">This PR has been replaced</p>
                                        <p class="text-sm text-purple-800 mt-1">
                                            Replaced by: 
                                            <a href="{{ route('purchase-requests.show', $purchaseRequest->replacedByPr) }}" class="font-mono font-bold hover:underline">
                                                {{ $purchaseRequest->replacedByPr->pr_number }}
                                            </a>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Items Card -->
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                        <div class="bg-gradient-to-r from-cagsu-maroon to-cagsu-orange px-6 py-4">
                            <h3 class="text-lg font-bold text-white">Requested Items</h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                @forelse($purchaseRequest->items as $item)
                                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <h4 class="font-semibold text-gray-900">{{ $item->item_name }}</h4>
                                            @if($item->description)
                                            <p class="text-sm text-gray-600 mt-1">{{ $item->description }}</p>
                                            @endif
                                            <div class="flex gap-4 mt-2">
                                                <span class="text-sm text-gray-600">
                                                    <span class="font-medium">Qty:</span> {{ $item->quantity }} {{ $item->unit }}
                                                </span>
                                                @if($item->estimated_unit_cost)
                                                <span class="text-sm text-gray-600">
                                                    <span class="font-medium">Unit Cost:</span> ₱{{ number_format($item->estimated_unit_cost, 2) }}
                                                </span>
                                                <span class="text-sm font-semibold text-gray-900">
                                                    <span class="font-medium">Total:</span> ₱{{ number_format($item->total_cost, 2) }}
                                                </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @empty
                                <p class="text-center text-gray-500 py-8">No items added yet</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Documents Card -->
                    @if($purchaseRequest->documents->count() > 0)
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                        <div class="bg-gradient-to-r from-cagsu-maroon to-cagsu-orange px-6 py-4">
                            <h3 class="text-lg font-bold text-white">Attached Documents</h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-3">
                                @foreach($purchaseRequest->documents as $document)
                                <a href="{{ Storage::url($document->file_path) }}" target="_blank" class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                                    <div class="flex items-center gap-3">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        </svg>
                                        <div>
                                            <p class="font-medium text-gray-900">{{ $document->file_name }}</p>
                                            <p class="text-xs text-gray-500 capitalize">{{ $document->document_type }}</p>
                                        </div>
                                    </div>
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                    </svg>
                                </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Right Column: Timeline -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden sticky top-6">
                        <div class="bg-gradient-to-r from-cagsu-maroon to-cagsu-orange px-6 py-4">
                            <h3 class="text-lg font-bold text-white">Activity Timeline</h3>
                        </div>
                        <div class="p-6 max-h-[800px] overflow-y-auto">
                            <x-pr-timeline :activities="$purchaseRequest->activities" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

