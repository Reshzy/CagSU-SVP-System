<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center gap-4">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-black leading-tight">
                {{ __('Project Procurement Management Plan (PPMP)') }} - FY {{ $fiscalYear }}
            </h2>
            <div class="flex gap-2">
                @if ($ppmp->status === 'draft' || $ppmp->items->count() === 0)
                    <a href="{{ route('ppmp.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        {{ $ppmp->items->count() > 0 ? 'Edit PPMP' : 'Create PPMP' }}
                    </a>
                @else
                    <a href="{{ route('ppmp.create') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        Edit PPMP
                    </a>
                @endif
                @if ($ppmp->items->count() > 0)
                    <a href="{{ route('ppmp.summary', $ppmp) }}" class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
                        View Summary
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Budget Status -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-bold mb-4 text-gray-800 dark:text-gray-200">Budget Status</h3>
                    <div class="grid grid-cols-4 gap-4 mb-4">
                        <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Allocated Budget</div>
                            <div class="text-2xl font-bold text-white">₱{{ number_format($budgetStatus['allocated'], 2) }}</div>
                        </div>
                        <div class="bg-purple-50 dark:bg-purple-900 p-4 rounded">
                            <div class="text-sm text-gray-600 dark:text-gray-400">PPMP Planned</div>
                            <div class="text-2xl font-bold text-white">₱{{ number_format($budgetStatus['planned'], 2) }}</div>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900 p-4 rounded">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Available</div>
                            <div class="text-2xl font-bold text-white">₱{{ number_format($budgetStatus['available'], 2) }}</div>
                        </div>
                        <div class="bg-orange-50 dark:bg-orange-900 p-4 rounded">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Utilization</div>
                            <div class="text-2xl font-bold text-white">{{ number_format($budgetStatus['utilization_percentage'], 1) }}%</div>
                        </div>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div class="w-full bg-gray-200 rounded-full h-4 dark:bg-gray-700">
                        <div class="h-4 rounded-full {{ $budgetStatus['is_within_budget'] ? 'bg-blue-600' : 'bg-red-600' }}" 
                             style="width: {{ min(100, $budgetStatus['utilization_percentage']) }}%"></div>
                    </div>
                </div>
            </div>

            <!-- PPMP Status -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200">PPMP Information</h3>
                        <span class="px-3 py-1 rounded-full text-sm font-semibold
                            {{ $ppmp->status === 'validated' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ ucfirst($ppmp->status) }}
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Department:</span>
                            <span class="font-semibold text-white">{{ $ppmp->department->name }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Total Items:</span>
                            <span class="font-semibold text-white">{{ $ppmp->items->count() }}</span>
                        </div>
                        @if ($ppmp->validated_at)
                            <div>
                                <span class="text-gray-600 dark:text-gray-400">Validated At:</span>
                                <span class="font-semibold text-white">{{ $ppmp->validated_at->format('M d, Y') }}</span>
                            </div>
                            <div>
                                <span class="text-gray-600 dark:text-gray-400">Validated By:</span>
                                <span class="font-semibold text-white">{{ $ppmp->validatedBy->name ?? 'N/A' }}</span>
                            </div>
                        @endif
                    </div>

                    @if ($ppmp->items->count() > 0 && $ppmp->status !== 'validated')
                        <form action="{{ route('ppmp.validate', $ppmp) }}" method="POST" class="mt-4">
                            @csrf
                            <button
                                type="submit"
                                class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                                onclick="return confirm('Are you sure you want to validate this PPMP? This will mark it as final for budget tracking.')"
                            >
                                Validate PPMP
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <!-- PPMP Items -->
            @if ($ppmp->items->count() > 0)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg text-white">
                    <div class="p-6">
                        <h3 class="text-lg font-bold mb-4 text-gray-800 dark:text-gray-200">PPMP Items</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Item</th>
                                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Q1</th>
                                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Q2</th>
                                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Q3</th>
                                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Q4</th>
                                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total Qty</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Unit Cost</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total Cost</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($ppmp->items as $item)
                                        <tr>
                                            <td class="px-4 py-2 text-sm">
                                                <div class="font-semibold">{{ $item->appItem->item_name }}</div>
                                                <div class="text-xs text-gray-500">{{ $item->appItem->item_code }}</div>
                                            </td>
                                            <td class="px-4 py-2 text-sm text-center">{{ $item->q1_quantity }}</td>
                                            <td class="px-4 py-2 text-sm text-center">{{ $item->q2_quantity }}</td>
                                            <td class="px-4 py-2 text-sm text-center">{{ $item->q3_quantity }}</td>
                                            <td class="px-4 py-2 text-sm text-center">{{ $item->q4_quantity }}</td>
                                            <td class="px-4 py-2 text-sm text-center font-semibold">{{ $item->total_quantity }}</td>
                                            <td class="px-4 py-2 text-sm text-right">₱{{ number_format($item->estimated_unit_cost, 2) }}</td>
                                            <td class="px-4 py-2 text-sm text-right font-semibold">₱{{ number_format($item->estimated_total_cost, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <td colspan="7" class="px-4 py-2 text-sm text-right font-bold">Grand Total:</td>
                                        <td class="px-4 py-2 text-sm text-right font-bold">₱{{ number_format($ppmp->total_estimated_cost, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center">
                        <p class="text-gray-500 mb-4">No PPMP items found. Create your PPMP by selecting items from the APP.</p>
                        <a href="{{ route('ppmp.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Create PPMP
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

