@section('title', 'Set Department Budget')

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
            {{ __('Set Budget for') }} {{ $department->name }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form action="{{ route('budget.update', $department) }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <x-input-label for="fiscal_year" value="Fiscal Year" />
                            <select id="fiscal_year" name="fiscal_year"
                                class="mt-1 block w-full border-gray-300 rounded-md" required>
                                @for ($year = date('Y') + 1; $year >= 2020; $year--)
                                <option value="{{ $year }}" {{ $fiscalYear == $year ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                                @endfor
                            </select>
                            <x-input-error :messages="$errors->get('fiscal_year')" class="mt-2" />
                        </div>

                        <div class="border-t pt-4">
                            <h3 class="text-lg font-semibold mb-4">Current Budget Status</h3>

                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div class="bg-blue-50 p-4 rounded-lg">
                                    <div class="text-sm text-gray-600">Current Allocated</div>
                                    <div class="text-2xl font-bold text-blue-600">
                                        ₱{{ number_format($budget->allocated_budget, 2) }}
                                    </div>
                                </div>

                                <div class="bg-red-50 p-4 rounded-lg">
                                    <div class="text-sm text-gray-600">Utilized</div>
                                    <div class="text-2xl font-bold text-red-600">
                                        ₱{{ number_format($budget->utilized_budget, 2) }}
                                    </div>
                                </div>

                                <div class="bg-yellow-50 p-4 rounded-lg">
                                    <div class="text-sm text-gray-600">Reserved (Pending PRs)</div>
                                    <div class="text-2xl font-bold text-yellow-600">
                                        ₱{{ number_format($budget->reserved_budget, 2) }}
                                    </div>
                                </div>

                                <div class="bg-green-50 p-4 rounded-lg">
                                    <div class="text-sm text-gray-600">Available</div>
                                    <div class="text-2xl font-bold text-green-600">
                                        ₱{{ number_format($budget->getAvailableBudget(), 2) }}
                                    </div>
                                </div>
                            </div>

                            @if($budget->utilized_budget > 0 || $budget->reserved_budget > 0)
                            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4">
                                <p class="font-medium">Warning:</p>
                                <p class="text-sm">This department has already utilized or reserved budget.
                                    Make sure the new allocated amount is sufficient to cover existing commitments
                                    (₱{{ number_format($budget->utilized_budget + $budget->reserved_budget, 2) }}).</p>
                            </div>
                            @endif
                        </div>

                        <div class="border-t pt-4">
                            <h3 class="text-lg font-semibold mb-4">Set New Budget</h3>

                            <div>
                                <x-input-label for="allocated_budget" value="Allocated Budget Amount (₱)" />
                                <x-text-input
                                    id="allocated_budget"
                                    name="allocated_budget"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="mt-1 block w-full"
                                    :value="old('allocated_budget', $budget->allocated_budget)"
                                    required />
                                <x-input-error :messages="$errors->get('allocated_budget')" class="mt-2" />
                                <p class="mt-1 text-sm text-gray-600">
                                    Enter the total budget allocated to this department for the fiscal year.
                                </p>
                            </div>

                            <div class="mt-4">
                                <x-input-label for="notes" value="Notes (Optional)" />
                                <textarea
                                    id="notes"
                                    name="notes"
                                    rows="3"
                                    class="mt-1 block w-full border-gray-300 rounded-md"
                                    placeholder="Add any notes or comments about this budget allocation...">{{ old('notes', $budget->notes) }}</textarea>
                                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 pt-4 border-t">
                            <a href="{{ route('budget.index', ['fiscal_year' => $fiscalYear]) }}"
                                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
                                Cancel
                            </a>
                            <x-primary-button>
                                Save Budget
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>