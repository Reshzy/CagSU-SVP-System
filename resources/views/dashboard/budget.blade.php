<!-- Budget Office Dashboard -->
@php
$year = now()->year;
$pendingEarmarks = \App\Models\PurchaseRequest::where('status', 'budget_office_review')->count();
$prYtd = (float) \App\Models\PurchaseRequest::whereYear('created_at', $year)->sum('estimated_total');
$poYtd = (float) \App\Models\PurchaseOrder::whereYear('created_at', $year)->sum('total_amount');
$utilization = $prYtd > 0 ? round(($poYtd / $prYtd) * 100, 1) : 0;
$available = max($prYtd - $poYtd, 0);
@endphp
<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg leading-6 font-medium text-gray-900 flex items-center">
            <svg class="w-6 h-6 text-cagsu-maroon mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Budget Management & Earmarking
        </h3>
    </div>
    <div class="px-6 py-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

            <!-- PR Earmarking Card -->
            <div class="p-6 border border-cagsu-yellow bg-cagsu-yellow bg-opacity-10 rounded-lg hover:shadow-lg transition-shadow">
                <div class="flex items-center mb-4">
                    <svg class="h-12 w-12 text-cagsu-orange mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    <div>
                        <h4 class="text-xl font-semibold text-gray-900">Purchase Request Earmarking</h4>
                        <p class="text-sm text-gray-600">Review and approve procurement details</p>
                    </div>
                </div>
                <div class="mb-4">
                    <span class="text-4xl font-bold text-cagsu-maroon">{{ $pendingEarmarks }}</span>
                    <span class="text-gray-600 ml-2">pending requests</span>
                </div>
                <a href="{{ route('budget.purchase-requests.index') }}"
                    class="inline-block px-6 py-3 bg-cagsu-yellow text-white rounded-md hover:bg-cagsu-orange transition-colors">
                    Review Purchase Requests →
                </a>
            </div>

            <!-- Department Budget Management Card -->
            <div class="p-6 border border-indigo-200 bg-indigo-50 rounded-lg hover:shadow-lg transition-shadow">
                <div class="flex items-center mb-4">
                    <svg class="h-12 w-12 text-indigo-600 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    <div>
                        <h4 class="text-xl font-semibold text-gray-900">Department Budget Management</h4>
                        <p class="text-sm text-gray-600">Set and monitor department budgets</p>
                    </div>
                </div>
                <div class="mb-4">
                    <span class="text-4xl font-bold text-indigo-600">{{ \App\Models\Department::active()->count() }}</span>
                    <span class="text-gray-600 ml-2">active departments</span>
                </div>
                <a href="{{ route('budget.index') }}"
                    class="inline-block px-6 py-3 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors">
                    Manage Department Budgets →
                </a>
            </div>

        </div>

        <!-- Stats Row -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Budget Utilization -->
            <div class="text-center p-6 border border-green-200 bg-green-50 rounded-lg">
                <svg class="h-12 w-12 text-green-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <h4 class="text-lg font-semibold text-gray-900">Budget Utilization</h4>
                <p class="text-3xl font-bold text-green-600 mt-2">{{ $utilization }}%</p>
                <p class="text-sm text-gray-600 mt-2">Year-to-date spending</p>
            </div>

            <!-- Available Budget -->
            <div class="text-center p-6 border border-blue-200 bg-blue-50 rounded-lg">
                <svg class="h-12 w-12 text-blue-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <h4 class="text-lg font-semibold text-gray-900">Available Budget</h4>
                <p class="text-3xl font-bold text-blue-600 mt-2">₱{{ number_format($available, 2) }}</p>
                <p class="text-sm text-gray-600 mt-2">Remaining allocation</p>
            </div>

            <!-- Total Allocated -->
            <div class="text-center p-6 border border-purple-200 bg-purple-50 rounded-lg">
                <svg class="h-12 w-12 text-purple-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h4 class="text-lg font-semibold text-gray-900">Total Allocated</h4>
                <p class="text-3xl font-bold text-purple-600 mt-2">₱{{ number_format($prYtd, 2) }}</p>
                <p class="text-sm text-gray-600 mt-2">Current fiscal year</p>
            </div>
        </div>
    </div>
</div>