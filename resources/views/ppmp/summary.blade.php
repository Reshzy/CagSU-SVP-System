<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('PPMP Summary') }} - {{ $ppmp->department->name }} - FY {{ $ppmp->fiscal_year }}
            </h2>
            <a href="{{ route('ppmp.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Back to PPMP
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 text-white">
            <!-- Budget Overview -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-bold mb-4 text-gray-800 dark:text-gray-200">Budget Overview</h3>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Allocated Budget</div>
                            <div class="text-2xl font-bold">₱{{ number_format($budgetStatus['allocated'], 2) }}</div>
                        </div>
                        <div class="bg-purple-50 dark:bg-purple-900 p-4 rounded">
                            <div class="text-sm text-gray-600 dark:text-gray-400">PPMP Total</div>
                            <div class="text-2xl font-bold">₱{{ number_format($budgetStatus['planned'], 2) }}</div>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900 p-4 rounded">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Remaining</div>
                            <div class="text-2xl font-bold">₱{{ number_format($budgetStatus['remaining_after_ppmp'], 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items by Category -->
            @foreach ($itemsByCategory as $category => $items)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-bold mb-4 text-gray-800 dark:text-gray-200">{{ $category }}</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Item</th>
                                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Q1</th>
                                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Q2</th>
                                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Q3</th>
                                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Q4</th>
                                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Cost</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($items as $item)
                                        <tr>
                                            <td class="px-4 py-2 text-sm">{{ $item->appItem->item_name }}</td>
                                            <td class="px-4 py-2 text-sm text-center">{{ $item->q1_quantity }}</td>
                                            <td class="px-4 py-2 text-sm text-center">{{ $item->q2_quantity }}</td>
                                            <td class="px-4 py-2 text-sm text-center">{{ $item->q3_quantity }}</td>
                                            <td class="px-4 py-2 text-sm text-center">{{ $item->q4_quantity }}</td>
                                            <td class="px-4 py-2 text-sm text-center font-semibold">{{ $item->total_quantity }}</td>
                                            <td class="px-4 py-2 text-sm text-right">₱{{ number_format($item->estimated_total_cost, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <td colspan="6" class="px-4 py-2 text-sm text-right font-bold">Category Total:</td>
                                        <td class="px-4 py-2 text-sm text-right font-bold">
                                            ₱{{ number_format($items->sum('estimated_total_cost'), 2) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>

