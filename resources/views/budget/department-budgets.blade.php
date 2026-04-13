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
                            class="border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-black">
                            @for ($year = date('Y') + 1; $year >= 2020; $year--)
                            <option value="{{ $year }}" {{ $fiscalYear == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                            @endfor
                        </select>
                    </form>
                </div>
            </div>

            <div class="mb-4">
                <h3 class="text-lg font-semibold text-black">Department Budget Overview - {{ $fiscalYear }}</h3>
            </div>

            <livewire:budget.department-budgets-table :fiscal-year="$fiscalYear" />
        </div>
    </div>
</x-app-layout>