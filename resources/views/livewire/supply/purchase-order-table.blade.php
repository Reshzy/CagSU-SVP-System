<div
    x-data="{
        saveFilters() {
            try {
                const q = $wire.poNumberSearch ?? '';
                const s = $wire.supplierFilter ?? '';
                const p = $wire.prNumberFilter ?? '';
                const t = $wire.statusFilter ?? '';
                localStorage.setItem('po_filters', JSON.stringify({
                    poNumberSearch: q,
                    supplierFilter: s,
                    prNumberFilter: p,
                    statusFilter: t
                }));
            } catch (e) {}
        },
        restoreFilters() {
            try {
                const saved = JSON.parse(localStorage.getItem('po_filters') || '{}');
                const hasSaved = saved.poNumberSearch !== undefined || saved.supplierFilter !== undefined || saved.prNumberFilter !== undefined || saved.statusFilter !== undefined;
                if (!hasSaved) return false;
                if (typeof $wire === 'undefined') return false;
                if (saved.poNumberSearch !== undefined) $wire.set('poNumberSearch', saved.poNumberSearch);
                if (saved.supplierFilter !== undefined) $wire.set('supplierFilter', saved.supplierFilter);
                if (saved.prNumberFilter !== undefined) $wire.set('prNumberFilter', saved.prNumberFilter);
                if (saved.statusFilter !== undefined) $wire.set('statusFilter', saved.statusFilter);
                return true;
            } catch (e) { return false; }
        },
        init() {
            const self = this;
            const params = new URLSearchParams(window.location.search);
            const hasUrlParams = params.has('po') || params.has('supplier') || params.has('pr') || params.has('statusFilter');
            let attempts = 0;
            const maxAttempts = 5;
            function attemptRestore() {
                if (hasUrlParams || attempts >= maxAttempts) return;
                attempts++;
                if (self.restoreFilters()) return;
                setTimeout(attemptRestore, 250);
            }
            setTimeout(attemptRestore, 400);
        }
    }"
    x-on:input.debounce.400ms="saveFilters()"
    x-on:change="saveFilters()"
>
    <!-- Search and Filters Section -->
    <div class="bg-white rounded-lg shadow-sm mb-6 p-4 dark:bg-gray-800">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <!-- PO Number Search -->
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1 dark:text-gray-400">PO Number</label>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="poNumberSearch"
                    placeholder="Search PO #..."
                    class="w-full border-gray-300 rounded-lg focus:border-cagsu-maroon focus:ring-cagsu-maroon dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                />
            </div>

            <!-- Supplier Name Dropdown -->
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1 dark:text-gray-400">Supplier</label>
                <select
                    wire:model.live="supplierFilter"
                    class="w-full border-gray-300 rounded-lg focus:border-cagsu-maroon focus:ring-cagsu-maroon dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                >
                    <option value="">All Suppliers</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->business_name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- PR Number Dropdown -->
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1 dark:text-gray-400">PR Number</label>
                <select
                    wire:model.live="prNumberFilter"
                    class="w-full border-gray-300 rounded-lg focus:border-cagsu-maroon focus:ring-cagsu-maroon dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                >
                    <option value="">All PR Numbers</option>
                    @foreach($prNumbers as $prNumber)
                        <option value="{{ $prNumber }}">{{ $prNumber }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1 dark:text-gray-400">Status</label>
                <select
                    wire:model.live="statusFilter"
                    class="w-full border-gray-300 rounded-lg focus:border-cagsu-maroon focus:ring-cagsu-maroon dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                >
                    <option value="">All Statuses</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Active Filters Row -->
        <div class="flex items-center justify-between mt-3">
            <div wire:loading class="text-sm text-gray-500 dark:text-gray-400">
                Searching...
            </div>
            <div wire:loading.remove class="flex-1"></div>

            @if($poNumberSearch || $supplierFilter || $prNumberFilter || $statusFilter)
                <button
                    wire:click="$set('poNumberSearch', ''); $set('supplierFilter', ''); $set('prNumberFilter', ''); $set('statusFilter', '')"
                    x-on:click="localStorage.removeItem('po_filters')"
                    class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition whitespace-nowrap text-sm dark:border-gray-600 dark:hover:bg-gray-700 dark:text-gray-300"
                >
                    Clear Filters
                </button>
            @endif
        </div>
    </div>

    <!-- Purchase Orders Table -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg dark:bg-gray-800">
        <div class="p-6 text-gray-900 dark:text-gray-100">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">PO #</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">PO Date</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">PR #</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Supplier</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Amount</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Status</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                        @forelse($orders as $po)
                        <tr wire:key="po-{{ $po->id }}">
                            <td class="px-4 py-2 font-mono">{{ $po->po_number }}</td>
                            <td class="px-4 py-2">{{ optional($po->po_date)->format('Y-m-d') }}</td>
                            <td class="px-4 py-2">{{ $po->purchaseRequest?->pr_number }}</td>
                            <td class="px-4 py-2">{{ $po->supplier?->business_name }}</td>
                            <td class="px-4 py-2">{{ number_format((float)$po->total_amount, 2) }}</td>
                            <td class="px-4 py-2 capitalize">{{ str_replace('_', ' ', $po->status) }}</td>
                            <td class="px-4 py-2 text-right">
                                <a href="{{ route('supply.purchase-orders.show', $po) }}" class="px-3 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                                    Details
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                                @if($poNumberSearch || $supplierFilter || $prNumberFilter || $statusFilter)
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
