<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-black leading-tight">
            {{ __('Consolidated Annual Procurement Plan (APP)') }} - FY {{ $fiscalYear }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="grid grid-cols-4 gap-4">
                        <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Validated PPMPs</div>
                            <div class="text-2xl font-bold dark:text-white">{{ $stats['validated_ppmps'] }}</div>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900 p-4 rounded">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Departments Included</div>
                            <div class="text-2xl font-bold dark:text-white">{{ $stats['departments_included'] }}</div>
                        </div>
                        <div class="bg-purple-50 dark:bg-purple-900 p-4 rounded">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Consolidated Items</div>
                            <div class="text-2xl font-bold dark:text-white">{{ $stats['total_items'] }}</div>
                        </div>
                        <div class="bg-orange-50 dark:bg-orange-900 p-4 rounded">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Grand Total</div>
                            <div class="text-2xl font-bold dark:text-white">₱{{ number_format($stats['grand_total_cost'], 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if ($groupedItems->isEmpty())
                        <p class="text-gray-500">No consolidated APP data found. Validate department PPMPs first.</p>
                    @else
                        @foreach ($groupedItems as $category => $items)
                            <div class="mb-6">
                                <h3 class="text-lg font-bold mb-2 text-gray-800 dark:text-gray-200">{{ $category }}</h3>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead class="bg-gray-50 dark:bg-gray-900">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Item Code</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Item Name</th>
                                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Departments</th>
                                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Q1</th>
                                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Q2</th>
                                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Q3</th>
                                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Q4</th>
                                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total Qty</th>
                                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total Cost</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                            @foreach ($items as $item)
                                                <tr>
                                                    <td class="px-4 py-2 text-sm dark:text-white">{{ $item->item_code }}</td>
                                                    <td class="px-4 py-2 text-sm dark:text-white">{{ $item->item_name }}</td>
                                                    <td class="px-4 py-2 text-sm text-center dark:text-white">{{ $item->department_count }}</td>
                                                    <td class="px-4 py-2 text-sm text-center dark:text-white">{{ $item->q1_quantity }}</td>
                                                    <td class="px-4 py-2 text-sm text-center dark:text-white">{{ $item->q2_quantity }}</td>
                                                    <td class="px-4 py-2 text-sm text-center dark:text-white">{{ $item->q3_quantity }}</td>
                                                    <td class="px-4 py-2 text-sm text-center dark:text-white">{{ $item->q4_quantity }}</td>
                                                    <td class="px-4 py-2 text-sm text-center dark:text-white">{{ $item->total_quantity }}</td>
                                                    <td class="px-4 py-2 text-sm text-right dark:text-white">₱{{ number_format((float) $item->estimated_total_cost, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
