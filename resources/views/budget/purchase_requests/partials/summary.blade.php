{{-- Shared PR summary card for Budget edit and amend views. Expects $purchaseRequest. --}}
<div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
    <div class="bg-gradient-to-r from-cagsu-maroon to-cagsu-orange px-6 py-4">
        <h3 class="text-base font-bold text-white">Purchase Request Summary</h3>
    </div>
    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Requester</div>
            <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $purchaseRequest->requester?->name ?? 'N/A' }}</div>
        </div>
        <div>
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Department</div>
            <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $purchaseRequest->department?->name ?? 'N/A' }}</div>
        </div>
        <div>
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Estimated Total</div>
            <div class="font-semibold text-gray-900 dark:text-gray-100">₱{{ number_format((float) $purchaseRequest->estimated_total, 2) }}</div>
        </div>
        <div class="md:col-span-3">
            <div class="flex items-center gap-2 mb-1">
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Purpose</div>
                <button type="button" id="copy-purpose-btn" class="inline-flex items-center text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200 transition-colors relative group">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 text-xs text-white bg-gray-900 rounded-md opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
                        Copy to remarks
                    </span>
                </button>
            </div>
            <div class="text-gray-900 dark:text-gray-100" id="purpose-text">{{ $purchaseRequest->purpose }}</div>
        </div>
    </div>
</div>
