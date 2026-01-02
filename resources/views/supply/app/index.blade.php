<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Annual Procurement Plan (APP)') }} - FY {{ $fiscalYear }}
            </h2>
            <a href="{{ route('supply.app.import') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Import APP CSV
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if (session('import_output'))
                <div class="bg-gray-100 border border-gray-400 text-gray-700 px-4 py-3 rounded relative mb-4">
                    <pre class="text-xs">{{ session('import_output') }}</pre>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="grid grid-cols-3 gap-4">
                        <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Total Items</div>
                            <div class="text-2xl font-bold">{{ $stats['total_items'] }}</div>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900 p-4 rounded">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Active Items</div>
                            <div class="text-2xl font-bold">{{ $stats['active_items'] }}</div>
                        </div>
                        <div class="bg-purple-50 dark:bg-purple-900 p-4 rounded">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Categories</div>
                            <div class="text-2xl font-bold">{{ $stats['categories_count'] }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if ($appItems->isEmpty())
                        <p class="text-gray-500">No APP items found. Please import the APP CSV file.</p>
                    @else
                        @foreach ($categories as $category)
                            <div class="mb-6">
                                <h3 class="text-lg font-bold mb-2 text-gray-800 dark:text-gray-200">{{ $category }}</h3>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead class="bg-gray-50 dark:bg-gray-900">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Item Code</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Item Name</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Unit</th>
                                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Unit Price</th>
                                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                            @foreach ($appItems[$category] as $item)
                                                <tr>
                                                    <td class="px-4 py-2 text-sm">{{ $item->item_code }}</td>
                                                    <td class="px-4 py-2 text-sm">{{ $item->item_name }}</td>
                                                    <td class="px-4 py-2 text-sm">{{ $item->unit_of_measure }}</td>
                                                    <td class="px-4 py-2 text-sm text-right">₱{{ number_format($item->unit_price, 2) }}</td>
                                                    <td class="px-4 py-2 text-sm text-center">
                                                        @if ($item->is_active)
                                                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">Active</span>
                                                        @else
                                                            <span class="bg-gray-100 text-gray-800 px-2 py-1 rounded text-xs">Inactive</span>
                                                        @endif
                                                    </td>
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

