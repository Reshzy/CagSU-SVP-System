@section('title', 'Department Budgets')

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('Department Budgets') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Fiscal Year Selector -->
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="GET" action="{{ route('budget.index') }}" class="flex items-center space-x-4">
                        <label for="fiscal_year" class="text-sm font-medium text-gray-700">Fiscal Year:</label>
                        <select name="fiscal_year" id="fiscal_year" onchange="this.form.submit()"
                            class="border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @for ($year = date('Y') + 1; $year >= 2020; $year--)
                            <option value="{{ $year }}" {{ $fiscalYear == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                            @endfor
                        </select>
                    </form>
                </div>
            </div>

            <!-- Success Message -->
            @if(session('success'))
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                {{ session('success') }}
            </div>
            @endif

            <!-- Department Budgets Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Department Budget Overview - {{ $fiscalYear }}</h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Department
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Allocated
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Utilized
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Reserved
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Available
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Utilization
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($departments as $dept)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $dept['name'] }}</div>
                                        <div class="text-sm text-gray-500">{{ $dept['code'] }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                        ₱{{ number_format($dept['allocated_budget'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                        ₱{{ number_format($dept['utilized_budget'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                        ₱{{ number_format($dept['reserved_budget'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold
                                            @if($dept['available_budget'] < 0)
                                                text-red-600
                                            @elseif($dept['available_budget'] < $dept['allocated_budget'] * 0.1)
                                                text-orange-600
                                            @else
                                                text-green-600
                                            @endif">
                                        ₱{{ number_format($dept['available_budget'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="flex items-center justify-end space-x-2">
                                            <div class="w-24 bg-gray-200 rounded-full h-2">
                                                <div class="h-2 rounded-full 
                                                        @if($dept['utilization_percentage'] >= 90)
                                                            bg-red-600
                                                        @elseif($dept['utilization_percentage'] >= 70)
                                                            bg-orange-600
                                                        @else
                                                            bg-green-600
                                                        @endif"
                                                    style="width: {{ min($dept['utilization_percentage'], 100) }}%">
                                                </div>
                                            </div>
                                            <span class="text-sm text-gray-600">
                                                {{ number_format($dept['utilization_percentage'], 1) }}%
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <a href="{{ route('budget.edit', ['department' => $dept['id'], 'fiscal_year' => $fiscalYear]) }}"
                                            class="text-indigo-600 hover:text-indigo-900 mr-3">
                                            Set Budget
                                        </a>
                                        <a href="{{ route('budget.show', ['department' => $dept['id'], 'fiscal_year' => $fiscalYear]) }}"
                                            class="text-blue-600 hover:text-blue-900">
                                            View Details
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                        No departments found.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr class="font-semibold">
                                    <td class="px-6 py-4 text-sm text-gray-900">TOTAL</td>
                                    <td class="px-6 py-4 text-right text-sm text-gray-900">
                                        ₱{{ number_format($departments->sum('allocated_budget'), 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm text-gray-900">
                                        ₱{{ number_format($departments->sum('utilized_budget'), 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm text-gray-900">
                                        ₱{{ number_format($departments->sum('reserved_budget'), 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm text-gray-900">
                                        ₱{{ number_format($departments->sum('available_budget'), 2) }}
                                    </td>
                                    <td class="px-6 py-4"></td>
                                    <td class="px-6 py-4"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>