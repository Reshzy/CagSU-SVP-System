<!-- Supply Officer Dashboard -->
@php
    $pendingPrCount = \App\Models\PurchaseRequest::whereIn('status', ['submitted','supply_office_review'])->count();
    $activePoCount = \App\Models\PurchaseOrder::whereIn('status', ['pending_approval','approved','sent_to_supplier','acknowledged_by_supplier','in_progress'])->count();
    $pendingDeliveryCount = \App\Models\PurchaseOrder::whereIn('status', ['sent_to_supplier','acknowledged_by_supplier','in_progress'])->count();

    // Workflow counts
    $countSubmitted = \App\Models\PurchaseRequest::where('status','submitted')->count();
    $countBudget = \App\Models\PurchaseRequest::where('status','budget_office_review')->count();
    $countBac = \App\Models\PurchaseRequest::whereIn('status',['bac_evaluation','bac_approved'])->count();
    $countPoGen = \App\Models\PurchaseRequest::where('status','po_generation')->count();
    $countDelivery = \App\Models\PurchaseRequest::whereIn('status',['supplier_processing','delivered'])->count();
    $countCompleted = \App\Models\PurchaseRequest::where('status','completed')->count();

    // Recent activity - PRs needing attention
    $recentPRs = \App\Models\PurchaseRequest::with(['requester', 'department'])
        ->whereIn('status', ['submitted','supply_office_review'])
        ->latest('submitted_at')
        ->take(5)
        ->get();

    // Average processing time for completed PRs (in days)
    $avgProcessingTime = \App\Models\PurchaseRequest::where('status', 'completed')
        ->whereNotNull('submitted_at')
        ->whereNotNull('completed_at')
        ->selectRaw('AVG(DATEDIFF(completed_at, submitted_at)) as avg_days')
        ->value('avg_days');
@endphp
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
    
    <!-- Pending PRs -->
    <div class="bg-white overflow-hidden shadow-lg rounded-lg hover:shadow-xl transition">
        <div class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Pending Review</dt>
                        <dd class="text-2xl font-bold text-gray-900">{{ $pendingPrCount }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-6 py-3">
            <div class="text-sm">
                <a href="{{ route('supply.purchase-requests.index') }}" class="font-medium text-cagsu-maroon hover:text-cagsu-orange">Review PRs →</a>
            </div>
        </div>
    </div>


    <!-- Active POs -->
    <div class="bg-white overflow-hidden shadow-lg rounded-lg hover:shadow-xl transition">
        <div class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Active POs</dt>
                        <dd class="text-2xl font-bold text-gray-900">{{ $activePoCount }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-6 py-3">
            <div class="text-sm">
                <a href="{{ route('supply.purchase-orders.index') }}" class="font-medium text-cagsu-maroon hover:text-cagsu-orange">Manage POs →</a>
            </div>
        </div>
    </div>

    <!-- Delivery Tracking -->
    <div class="bg-white overflow-hidden shadow-lg rounded-lg hover:shadow-xl transition">
        <div class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-cagsu-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Pending Delivery</dt>
                        <dd class="text-2xl font-bold text-gray-900">{{ $pendingDeliveryCount }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-6 py-3">
            <div class="text-sm">
                <a href="{{ route('supply.inventory-receipts.index') }}" class="font-medium text-cagsu-maroon hover:text-cagsu-orange">Track deliveries →</a>
            </div>
        </div>
    </div>

    <!-- Suppliers -->
    <div class="bg-white overflow-hidden shadow-lg rounded-lg hover:shadow-xl transition">
        <div class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Active Suppliers</dt>
                        <dd class="text-2xl font-bold text-gray-900">{{ \App\Models\Supplier::where('status', 'active')->count() }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-6 py-3">
            <div class="text-sm">
                <a href="{{ route('supply.suppliers.index') }}" class="font-medium text-cagsu-maroon hover:text-cagsu-orange">Manage suppliers →</a>
            </div>
        </div>
    </div>

    <!-- APP Management -->
    <div class="bg-white overflow-hidden shadow-lg rounded-lg hover:shadow-xl transition">
        <div class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">APP Items</dt>
                        <dd class="text-2xl font-bold text-gray-900">{{ \App\Models\AppItem::where('is_active', true)->count() }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-6 py-3">
            <div class="text-sm">
                <a href="{{ route('supply.app.index') }}" class="font-medium text-cagsu-maroon hover:text-cagsu-orange">Manage APP →</a>
            </div>
        </div>
    </div>

</div>

<!-- Management Tools Section -->
<div class="mt-6 bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg leading-6 font-medium text-gray-900">Management Tools</h3>
    </div>
    <div class="px-6 py-4">
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('supply.po-signatories.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-cagsu-blue hover:bg-blue-800 text-white text-sm font-medium rounded-md shadow-sm transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                Manage PO Signatories
            </a>
            <a href="{{ route('supply.suppliers.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-cagsu-maroon hover:bg-cagsu-orange text-white text-sm font-medium rounded-md shadow-sm transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                Manage Suppliers
            </a>
            <a href="{{ route('supply.app.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-md shadow-sm transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                </svg>
                Manage APP
            </a>
        </div>
    </div>
</div>

<!-- Recent Activity Section -->
<div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Recent PRs Needing Attention -->
    <div class="lg:col-span-2 bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">Recent PRs Needing Attention</h3>
            <a href="{{ route('supply.purchase-requests.index') }}" class="text-sm text-cagsu-maroon hover:text-cagsu-orange font-medium">View all →</a>
        </div>
        <div class="divide-y divide-gray-200">
            @forelse($recentPRs as $pr)
                <a href="{{ route('supply.purchase-requests.show', $pr) }}" class="block px-6 py-4 hover:bg-gray-50 transition">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-mono text-sm font-semibold text-cagsu-maroon">{{ $pr->pr_number }}</span>
                            </div>
                            <p class="text-sm text-gray-900 truncate">{{ $pr->purpose }}</p>
                            <div class="flex items-center gap-4 mt-1 text-xs text-gray-500">
                                <span>{{ $pr->department?->name }}</span>
                                <span>{{ $pr->requester?->name }}</span>
                                <span>₱{{ number_format($pr->estimated_total, 2) }}</span>
                            </div>
                        </div>
                        <div class="ml-4 flex-shrink-0">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </div>
                </a>
            @empty
                <div class="px-6 py-8 text-center text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="mt-2">All caught up! No pending PRs.</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="space-y-6">
        <!-- Average Processing Time -->
        @if($avgProcessingTime)
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Avg. Processing Time</p>
                    <p class="text-2xl font-bold text-gray-900">{{ round($avgProcessingTime) }} days</p>
                </div>
            </div>
        </div>
        @endif

    </div>
</div>

<!-- Workflow Overview -->
<div class="mt-6">
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Procurement Workflow Overview</h3>
            <span class="text-sm text-gray-500">CagSU 6-Step Process</span>
        </div>
        <div class="px-6 py-4">
            <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                
                <!-- Step 1: PR Submission -->
                <div class="text-center">
                    <div class="w-12 h-12 bg-cagsu-yellow text-white rounded-full flex items-center justify-center mx-auto mb-2">
                        <span class="font-bold">1</span>
                    </div>
                    <h4 class="text-sm font-medium text-gray-900">PR Submission</h4>
                    <p class="text-xs text-gray-500 mt-1">Control Number Assignment</p>
                    <div class="mt-2 text-lg font-bold text-cagsu-maroon">{{ $countSubmitted }}</div>
                </div>

                <!-- Step 2: Budget Review -->
                <div class="text-center">
                    <div class="w-12 h-12 bg-cagsu-orange text-white rounded-full flex items-center justify-center mx-auto mb-2">
                        <span class="font-bold">2</span>
                    </div>
                    <h4 class="text-sm font-medium text-gray-900">Budget Review</h4>
                    <p class="text-xs text-gray-500 mt-1">Earmarking Process</p>
                    <div class="mt-2 text-lg font-bold text-cagsu-maroon">{{ $countBudget }}</div>
                </div>

                <!-- Step 3: BAC Evaluation -->
                <div class="text-center">
                    <div class="w-12 h-12 bg-cagsu-maroon text-white rounded-full flex items-center justify-center mx-auto mb-2">
                        <span class="font-bold">3</span>
                    </div>
                    <h4 class="text-sm font-medium text-gray-900">BAC Evaluation</h4>
                    <p class="text-xs text-gray-500 mt-1">Quotation Review</p>
                    <div class="mt-2 text-lg font-bold text-cagsu-maroon">{{ $countBac }}</div>
                </div>

                <!-- Step 4: PO Generation -->
                <div class="text-center">
                    <div class="w-12 h-12 bg-blue-500 text-white rounded-full flex items-center justify-center mx-auto mb-2">
                        <span class="font-bold">4</span>
                    </div>
                    <h4 class="text-sm font-medium text-gray-900">PO Generation</h4>
                    <p class="text-xs text-gray-500 mt-1">Order Processing</p>
                    <div class="mt-2 text-lg font-bold text-cagsu-maroon">{{ $countPoGen }}</div>
                </div>

                <!-- Step 5: Delivery -->
                <div class="text-center">
                    <div class="w-12 h-12 bg-green-500 text-white rounded-full flex items-center justify-center mx-auto mb-2">
                        <span class="font-bold">5</span>
                    </div>
                    <h4 class="text-sm font-medium text-gray-900">Delivery</h4>
                    <p class="text-xs text-gray-500 mt-1">Supplier Processing</p>
                    <div class="mt-2 text-lg font-bold text-cagsu-maroon">{{ $countDelivery }}</div>
                </div>

                <!-- Step 6: Distribution -->
                <div class="text-center">
                    <div class="w-12 h-12 bg-purple-500 text-white rounded-full flex items-center justify-center mx-auto mb-2">
                        <span class="font-bold">6</span>
                    </div>
                    <h4 class="text-sm font-medium text-gray-900">Distribution</h4>
                    <p class="text-xs text-gray-500 mt-1">End User Receipt</p>
                    <div class="mt-2 text-lg font-bold text-cagsu-maroon">{{ $countCompleted }}</div>
                </div>

            </div>
        </div>
    </div>
</div>