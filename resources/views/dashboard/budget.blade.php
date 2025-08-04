<!-- Budget Office Dashboard -->
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
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <!-- Pending Earmarking -->
            <div class="text-center p-6 border border-cagsu-yellow bg-cagsu-yellow bg-opacity-10 rounded-lg">
                <svg class="h-12 w-12 text-cagsu-orange mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h4 class="text-lg font-semibold text-gray-900">Pending Earmarks</h4>
                <p class="text-3xl font-bold text-cagsu-maroon mt-2">0</p>
                <p class="text-sm text-gray-600 mt-2">Requests waiting for budget allocation</p>
            </div>

            <!-- Budget Utilization -->
            <div class="text-center p-6 border border-green-200 bg-green-50 rounded-lg">
                <svg class="h-12 w-12 text-green-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <h4 class="text-lg font-semibold text-gray-900">Budget Utilization</h4>
                <p class="text-3xl font-bold text-green-600 mt-2">0%</p>
                <p class="text-sm text-gray-600 mt-2">Year-to-date spending</p>
            </div>

            <!-- Available Budget -->
            <div class="text-center p-6 border border-blue-200 bg-blue-50 rounded-lg">
                <svg class="h-12 w-12 text-blue-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <h4 class="text-lg font-semibold text-gray-900">Available Budget</h4>
                <p class="text-3xl font-bold text-blue-600 mt-2">₱0.00</p>
                <p class="text-sm text-gray-600 mt-2">Remaining allocation</p>
            </div>

        </div>
    </div>
</div>