@section('title', 'Department Budget Details')

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
                {{ __('Budget Details:') }} {{ $department->name }}
            </h2>
            <a href="{{ route('budget.index', ['fiscal_year' => $fiscalYear]) }}"
                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
                Back to All Departments
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Budget Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm text-gray-600 mb-2">Allocated Budget</div>
                        <div class="text-3xl font-bold text-blue-600">
                            ₱{{ number_format($budget->allocated_budget, 2) }}
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm text-gray-600 mb-2">Utilized (Completed)</div>
                        <div class="text-3xl font-bold text-red-600">
                            ₱{{ number_format($budget->utilized_budget, 2) }}
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            {{ $budget->getUtilizationPercentage() }}% of allocated
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm text-gray-600 mb-2">Reserved (Pending)</div>
                        <div class="text-3xl font-bold text-yellow-600">
                            ₱{{ number_format($budget->reserved_budget, 2) }}
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm text-gray-600 mb-2">Available</div>
                        <div class="text-3xl font-bold 
                            @if($budget->getAvailableBudget() < 0)
                                text-red-600
                            @elseif($budget->getAvailableBudget() < $budget->allocated_budget * 0.1)
                                text-orange-600
                            @else
                                text-green-600
                            @endif">
                            ₱{{ number_format($budget->getAvailableBudget(), 2) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Budget Notes -->
            @if($budget->notes)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-2">Budget Notes</h3>
                    <p class="text-gray-700">{{ $budget->notes }}</p>
                </div>
            </div>
            @endif

            <!-- Purchase Requests Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Purchase Requests (FY {{ $fiscalYear }})</h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        PR Number
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Purpose
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Total Cost
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Date
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($purchaseRequests as $pr)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $pr['pr_number'] }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ $pr['purpose'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                                @if($pr['status'] === 'completed')
                                                    bg-green-100 text-green-800
                                                @elseif(in_array($pr['status'], ['cancelled', 'rejected']))
                                                    bg-red-100 text-red-800
                                                @else
                                                    bg-yellow-100 text-yellow-800
                                                @endif">
                                            {{ ucwords(str_replace('_', ' ', $pr['status'])) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                        ₱{{ number_format($pr['total_cost'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                        {{ $pr['created_at']->format('M d, Y') }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                        No purchase requests found for this fiscal year.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr class="font-semibold">
                                    <td colspan="3" class="px-6 py-4 text-sm text-gray-900">TOTAL</td>
                                    <td class="px-6 py-4 text-right text-sm text-gray-900">
                                        ₱{{ number_format($purchaseRequests->sum('total_cost'), 2) }}
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Actions</h3>
                    <a href="{{ route('budget.edit', ['department' => $department->id, 'fiscal_year' => $fiscalYear]) }}"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Update Budget Allocation
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>