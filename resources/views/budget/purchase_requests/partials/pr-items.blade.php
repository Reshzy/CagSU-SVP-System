{{-- Shared PR items (and lots) reference table for Budget edit and amend views. Expects $purchaseRequest with items and lotChildren loaded. --}}
<div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">PR Items (Reference)</h3>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            These are the original PR items and lots, shown for context. The Object of Expenditures above is what prints to A19+ in the earmark template.
        </p>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Item</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Unit</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Qty</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Unit Cost</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($purchaseRequest->items->load('lotChildren') as $item)
                    @if($item->isLotChild()) @continue @endif

                    @if($item->isLotHeader())
                        <tr class="bg-indigo-50 dark:bg-indigo-900/30">
                            <td class="px-4 py-2 text-sm font-semibold text-indigo-900 dark:text-indigo-100 uppercase">
                                {{ strtoupper($item->lot_name ?? $item->item_name) }}
                            </td>
                            <td class="px-4 py-2 text-sm font-semibold text-indigo-700 dark:text-indigo-200 uppercase">lot</td>
                            <td class="px-4 py-2 text-sm text-right text-indigo-900 dark:text-indigo-100">1</td>
                            <td class="px-4 py-2 text-sm text-right text-indigo-900 dark:text-indigo-100">
                                ₱{{ number_format((float) ($item->estimated_unit_cost ?? 0), 2) }}
                            </td>
                            <td class="px-4 py-2 text-sm text-right font-semibold text-indigo-900 dark:text-indigo-100">
                                ₱{{ number_format((float) ($item->estimated_total_cost ?? 0), 2) }}
                            </td>
                        </tr>

                        @foreach($item->lotChildren as $child)
                            <tr class="bg-indigo-50/30 dark:bg-indigo-900/10">
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 pl-8">
                                    <span class="text-indigo-400 mr-1">↳</span>
                                    {{ $child->quantity_requested }} {{ $child->unit_of_measure }}, {{ $child->item_name }}
                                </td>
                                <td class="px-4 py-2"></td>
                                <td class="px-4 py-2"></td>
                                <td class="px-4 py-2"></td>
                                <td class="px-4 py-2"></td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">{{ $item->item_name }}</td>
                            <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ $item->unit_of_measure }}</td>
                            <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-gray-100">{{ $item->quantity_requested }}</td>
                            <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-gray-100">₱{{ number_format((float) $item->estimated_unit_cost, 2) }}</td>
                            <td class="px-4 py-2 text-sm text-right font-medium text-gray-900 dark:text-gray-100">₱{{ number_format((float) ($item->estimated_total_cost ?? $item->estimated_unit_cost * $item->quantity_requested), 2) }}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
</div>
