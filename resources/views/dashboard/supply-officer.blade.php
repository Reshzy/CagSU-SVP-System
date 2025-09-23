<!-- Supply Officer Dashboard -->
@php
    $pendingPrCount = \App\Models\PurchaseRequest::whereIn('status', ['submitted','supply_office_review'])->count();
    $activePoCount = \App\Models\PurchaseOrder::whereIn('status', ['pending_approval','approved','sent_to_supplier','acknowledged_by_supplier','in_progress'])->count();
    $pendingDeliveryCount = \App\Models\PurchaseOrder::whereIn('status', ['sent_to_supplier','acknowledged_by_supplier','in_progress'])->count();

    $countSubmitted = \App\Models\PurchaseRequest::where('status','submitted')->count();
    $countBudget = \App\Models\PurchaseRequest::where('status','budget_office_review')->count();
    $countBac = \App\Models\PurchaseRequest::whereIn('status',['bac_evaluation','bac_approved'])->count();
    $countPoGen = \App\Models\PurchaseRequest::where('status','po_generation')->count();
    $countDelivery = \App\Models\PurchaseRequest::whereIn('status',['supplier_processing','delivered'])->count();
    $countCompleted = \App\Models\PurchaseRequest::where('status','completed')->count();
@endphp
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    
    <!-- Pending PRs -->
    <div class="bg-white overflow-hidden shadow-lg rounded-lg">
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
                        <dd class="text-lg font-medium text-gray-900">{{ $pendingPrCount }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-6 py-3">
            <div class="text-sm">
                <a href="{{ route('supply.purchase-requests.index') }}" class="font-medium text-cagsu-maroon hover:text-cagsu-orange">Review PRs</a>
            </div>
        </div>
    </div>

    <!-- Active POs -->
    <div class="bg-white overflow-hidden shadow-lg rounded-lg">
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
                        <dd class="text-lg font-medium text-gray-900">{{ $activePoCount }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-6 py-3">
            <div class="text-sm">
                <a href="{{ route('supply.purchase-orders.index') }}" class="font-medium text-cagsu-maroon hover:text-cagsu-orange">Manage POs</a>
            </div>
        </div>
    </div>

    <!-- Delivery Tracking -->
    <div class="bg-white overflow-hidden shadow-lg rounded-lg">
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
                        <dd class="text-lg font-medium text-gray-900">{{ $pendingDeliveryCount }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-6 py-3">
            <div class="text-sm">
                <a href="{{ route('supply.inventory-receipts.index') }}" class="font-medium text-cagsu-maroon hover:text-cagsu-orange">Track deliveries</a>
            </div>
        </div>
    </div>

    <!-- Suppliers -->
    <div class="bg-white overflow-hidden shadow-lg rounded-lg">
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
                        <dd class="text-lg font-medium text-gray-900">{{ \App\Models\Supplier::where('status', 'active')->count() }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-6 py-3">
            <div class="text-sm">
                <a href="{{ route('supply.suppliers.index') }}" class="font-medium text-cagsu-maroon hover:text-cagsu-orange">Manage suppliers</a>
            </div>
        </div>
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