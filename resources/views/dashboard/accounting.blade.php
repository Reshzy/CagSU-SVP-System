<!-- Accounting Office Dashboard -->
<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg leading-6 font-medium text-gray-900 flex items-center">
            <svg class="w-6 h-6 text-cagsu-maroon mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
            </svg>
            Payment Processing & Financial Management
        </h3>
    </div>
    <div class="px-6 py-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <!-- Pending Payments -->
            <div class="text-center p-6 border border-red-200 bg-red-50 rounded-lg">
                <svg class="h-12 w-12 text-red-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h4 class="text-lg font-semibold text-gray-900">Pending Payments</h4>
                <p class="text-3xl font-bold text-red-600 mt-2">0</p>
                <p class="text-sm text-gray-600 mt-2">Disbursement vouchers to process</p>
            </div>

            <!-- Processed This Month -->
            <div class="text-center p-6 border border-green-200 bg-green-50 rounded-lg">
                <svg class="h-12 w-12 text-green-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h4 class="text-lg font-semibold text-gray-900">Processed This Month</h4>
                <p class="text-3xl font-bold text-green-600 mt-2">0</p>
                <p class="text-sm text-gray-600 mt-2">Completed payments</p>
            </div>

            <!-- Total Amount -->
            <div class="text-center p-6 border border-blue-200 bg-blue-50 rounded-lg">
                <svg class="h-12 w-12 text-blue-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <h4 class="text-lg font-semibold text-gray-900">Total Processed</h4>
                <p class="text-3xl font-bold text-blue-600 mt-2">₱0.00</p>
                <p class="text-sm text-gray-600 mt-2">Monthly total</p>
            </div>

        </div>
    </div>
</div>