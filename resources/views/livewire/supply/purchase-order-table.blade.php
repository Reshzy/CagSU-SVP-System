<div>
    <!-- Search and Filters Section -->
    <div class="bg-white rounded-lg shadow-sm mb-6 p-4">
        <div class="flex flex-col gap-4 md:flex-row md:items-center">
            <!-- Search Input -->
            <div class="flex-1">
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search by PO #, PR #, or Supplier..." 
                    class="w-full border-gray-300 rounded-lg focus:border-cagsu-maroon focus:ring-cagsu-maroon"
                />
            </div>

            <!-- Status Filter -->
            <div class="w-full md:w-64">
                <select 
                    wire:model.live="statusFilter"
                    class="w-full border-gray-300 rounded-lg focus:border-cagsu-maroon focus:ring-cagsu-maroon"
                >
                    <option value="">All Statuses</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Clear Filters -->
            @if($search || $statusFilter)
                <button 
                    wire:click="$set('search', ''); $set('statusFilter', '')"
                    class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition whitespace-nowrap"
                >
                    Clear Filters
                </button>
            @endif
        </div>

        <!-- Loading Indicator -->
        <div wire:loading class="mt-2 text-sm text-gray-500">
            Searching...
        </div>
    </div>

    <!-- Purchase Orders Table -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PO #</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PO Date</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PR #</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($orders as $po)
                        <tr>
                            <td class="px-4 py-2 font-mono">{{ $po->po_number }}</td>
                            <td class="px-4 py-2">{{ optional($po->po_date)->format('Y-m-d') }}</td>
                            <td class="px-4 py-2">{{ $po->purchaseRequest?->pr_number }}</td>
                            <td class="px-4 py-2">{{ $po->supplier?->business_name }}</td>
                            <td class="px-4 py-2">{{ number_format((float)$po->total_amount, 2) }}</td>
                            <td class="px-4 py-2 capitalize">{{ str_replace('_', ' ', $po->status) }}</td>
                            <td class="px-4 py-2 text-right">
                                <a href="{{ route('supply.purchase-orders.show', $po) }}" class="px-3 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition">
                                    Details
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-gray-500">
                                @if($search || $statusFilter)
                                    No purchase orders found matching your search criteria.
                                @else
                                    No purchase orders.
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $orders->links() }}
            </div>
        </div>
    </div>
</div>
