@section('title', 'Supply Officer - Purchase Requests')

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('Review Purchase Requests') }}</h2>
            <div class="text-sm text-gray-600">
                {{ $requests->total() }} {{ Str::plural('request', $requests->total()) }} found
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Search and Filters -->
            <div class="bg-white rounded-lg shadow-sm mb-6" x-data="{ showFilters: false }">
                <div class="p-4">
                    <form method="GET" action="{{ route('supply.purchase-requests.index') }}" class="space-y-4">
                        
                        <!-- Search Bar -->
                        <div class="flex gap-3">
                            <div class="flex-1">
                                <input 
                                    type="text" 
                                    name="search" 
                                    value="{{ $searchTerm }}"
                                    placeholder="Search by PR #, purpose, or requester..." 
                                    class="w-full border-gray-300 rounded-lg focus:border-cagsu-maroon focus:ring-cagsu-maroon"
                                />
                            </div>
                            <button type="submit" class="px-6 py-2 bg-cagsu-maroon text-white rounded-lg hover:bg-cagsu-orange transition">
                                Search
                            </button>
                            <button type="button" @click="showFilters = !showFilters" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                </svg>
                                Filters
                            </button>
                        </div>

                        <!-- Advanced Filters -->
                        <div x-show="showFilters" x-cloak class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 pt-4 border-t">
                            
                            <!-- Status Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select name="status" class="w-full border-gray-300 rounded-lg focus:border-cagsu-maroon focus:ring-cagsu-maroon">
                                    <option value="">All Active</option>
                                    <option value="submitted" @selected($statusFilter==='submitted')>Submitted</option>
                                    <option value="supply_office_review" @selected($statusFilter==='supply_office_review')>In Review</option>
                                    <option value="budget_office_review" @selected($statusFilter==='budget_office_review')>Budget Review</option>
                                    <option value="bac_evaluation" @selected($statusFilter==='bac_evaluation')>BAC Evaluation</option>
                                    <option value="bac_approved" @selected($statusFilter==='bac_approved')>BAC Approved</option>
                                    <option value="partial_po_generation" @selected($statusFilter==='partial_po_generation')>Partial PO Generation</option>
                                    <option value="po_generation" @selected($statusFilter==='po_generation')>PO Generation</option>
                                    <option value="rejected" @selected($statusFilter==='rejected')>Rejected</option>
                                    <option value="cancelled" @selected($statusFilter==='cancelled')>Cancelled</option>
                                </select>
                            </div>

                            <!-- Department Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                                <select name="department" class="w-full border-gray-300 rounded-lg focus:border-cagsu-maroon focus:ring-cagsu-maroon">
                                    <option value="">All Departments</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" @selected($departmentFilter == $dept->id)>{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Date From -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                                <input type="date" name="date_from" value="{{ $dateFrom?->format('Y-m-d') }}" class="w-full border-gray-300 rounded-lg focus:border-cagsu-maroon focus:ring-cagsu-maroon">
                            </div>

                            <!-- Date To -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                                <input type="date" name="date_to" value="{{ $dateTo?->format('Y-m-d') }}" class="w-full border-gray-300 rounded-lg focus:border-cagsu-maroon focus:ring-cagsu-maroon">
                            </div>

                            <!-- Filter Actions -->
                            <div class="md:col-span-2 lg:col-span-4 flex gap-2">
                                <button type="submit" class="px-4 py-2 bg-cagsu-maroon text-white rounded-lg hover:bg-cagsu-orange transition">
                                    Apply Filters
                                </button>
                                <a href="{{ route('supply.purchase-requests.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                                    Clear All
                                </a>
                            </div>
                        </div>

                    </form>
                </div>
            </div>

            <!-- Purchase Requests List -->
            <div class="space-y-4">
                @forelse($requests as $req)
                    <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition">
                        <div class="p-6">
                            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                                
                                <!-- Left Section: PR Info -->
                                <div class="flex-1 space-y-3">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <div class="flex items-center gap-3">
                                                <a href="{{ route('supply.purchase-requests.show', $req) }}" class="font-mono text-lg font-semibold text-cagsu-maroon hover:text-cagsu-orange">
                                                    {{ $req->pr_number }}
                                                </a>
                                                
                                                <!-- Status Badge -->
                                                @php
                                                    $statusColors = [
                                                        'submitted' => 'bg-blue-100 text-blue-800',
                                                        'supply_office_review' => 'bg-yellow-100 text-yellow-800',
                                                        'budget_office_review' => 'bg-purple-100 text-purple-800',
                                                        'bac_evaluation' => 'bg-orange-100 text-orange-800',
                                                        'bac_approved' => 'bg-green-100 text-green-800',
                                                        'partial_po_generation' => 'bg-cyan-100 text-cyan-800',
                                                        'po_generation' => 'bg-indigo-100 text-indigo-800',
                                                        'rejected' => 'bg-red-100 text-red-800',
                                                        'cancelled' => 'bg-gray-100 text-gray-800',
                                                    ];
                                                    $statusColor = $statusColors[$req->status] ?? 'bg-gray-100 text-gray-800';
                                                    $statusDisplay = $req->status === 'rejected' ? 'Deferred' : str_replace('_', ' ', Str::title($req->status));
                                                @endphp
                                                <span class="px-3 py-1 text-xs font-semibold rounded-full {{ $statusColor }}">
                                                    {{ $statusDisplay }}
                                                </span>
                                            </div>
                                            
                                            <p class="text-gray-900 font-medium mt-2">{{ Str::limit($req->purpose, 80) }}</p>
                                        </div>
                                    </div>

                                    <!-- Details Grid -->
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                                        <div>
                                            <span class="text-gray-500">Requester:</span>
                                            <p class="font-medium text-gray-900">{{ $req->requester?->name }}</p>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Department:</span>
                                            <p class="font-medium text-gray-900">{{ $req->department?->name }}</p>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Items:</span>
                                            <p class="font-medium text-gray-900">{{ $req->items_count }} {{ Str::plural('item', $req->items_count) }}</p>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Estimated Total:</span>
                                            <p class="font-medium text-gray-900">₱{{ number_format($req->estimated_total, 2) }}</p>
                                        </div>
                                    </div>

                                    <div class="text-xs text-gray-500">
                                        Submitted {{ $req->submitted_at?->diffForHumans() ?? $req->created_at->diffForHumans() }}
                                    </div>
                                </div>

                                <!-- Right Section: Actions -->
                                <div class="flex flex-col gap-2 lg:items-end">
                                    <a href="{{ route('supply.purchase-requests.show', $req) }}" class="inline-flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        View Details
                                    </a>
                                    
                                    @if(in_array($req->status, ['bac_evaluation','bac_approved','partial_po_generation']))
                                        <a href="{{ route('supply.purchase-orders.preview', $req) }}" class="inline-flex items-center justify-center px-4 py-2 bg-cagsu-maroon text-white rounded-lg hover:bg-cagsu-orange transition">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            Create PO
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="bg-white rounded-lg shadow-sm p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No purchase requests found</h3>
                        <p class="mt-1 text-sm text-gray-500">Try adjusting your search or filters.</p>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if($requests->hasPages())
                <div class="mt-6">
                    {{ $requests->links() }}
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
