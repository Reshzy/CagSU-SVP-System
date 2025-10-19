@section('title', 'Budget Office - Earmark Review')

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('Earmark Review: ') . $purchaseRequest->pr_number }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-6">
                    <div>
                        <div class="text-sm text-gray-600">Purpose</div>
                        <div class="font-medium">{{ $purchaseRequest->purpose }}</div>
                    </div>

                    <div>
                        <div class="text-sm text-gray-600">Estimated Total</div>
                        <div class="font-medium">{{ number_format((float)$purchaseRequest->estimated_total, 2) }}</div>
                    </div>

                    <div>
                        <div class="text-sm text-gray-600">Items</div>
                        <ul class="list-disc ml-5">
                            @foreach($purchaseRequest->items as $it)
                            <li>{{ $it->item_name }} ({{ $it->quantity_requested }} x {{ number_format((float)$it->estimated_unit_cost,2) }})</li>
                            @endforeach
                        </ul>
                    </div>

                    <form method="POST" action="{{ route('budget.purchase-requests.update', $purchaseRequest) }}" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div class="border-t pt-4">
                            <h3 class="text-lg font-semibold mb-4">Procurement Details</h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <x-input-label for="date_needed" value="Date Needed" />
                                    <x-text-input id="date_needed" name="date_needed" type="date" class="mt-1 block w-full"
                                        :value="old('date_needed', $purchaseRequest->date_needed)" required />
                                    <x-input-error :messages="$errors->get('date_needed')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="funding_source" value="Funding Source" />
                                    <x-text-input id="funding_source" name="funding_source" type="text" class="mt-1 block w-full"
                                        :value="old('funding_source', $purchaseRequest->funding_source)" />
                                    <x-input-error :messages="$errors->get('funding_source')" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <x-input-label for="budget_code" value="Budget Code" />
                                    <x-text-input id="budget_code" name="budget_code" type="text" class="mt-1 block w-full"
                                        :value="old('budget_code', $purchaseRequest->budget_code)" />
                                    <x-input-error :messages="$errors->get('budget_code')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="procurement_type" value="Procurement Type" />
                                    <select id="procurement_type" name="procurement_type" class="mt-1 block w-full border-gray-300 rounded-md" required>
                                        <option value="">Select Type</option>
                                        <option value="supplies_materials" {{ old('procurement_type', $purchaseRequest->procurement_type) == 'supplies_materials' ? 'selected' : '' }}>Supplies/Materials</option>
                                        <option value="equipment" {{ old('procurement_type', $purchaseRequest->procurement_type) == 'equipment' ? 'selected' : '' }}>Equipment</option>
                                        <option value="infrastructure" {{ old('procurement_type', $purchaseRequest->procurement_type) == 'infrastructure' ? 'selected' : '' }}>Infrastructure</option>
                                        <option value="services" {{ old('procurement_type', $purchaseRequest->procurement_type) == 'services' ? 'selected' : '' }}>Services</option>
                                        <option value="consulting_services" {{ old('procurement_type', $purchaseRequest->procurement_type) == 'consulting_services' ? 'selected' : '' }}>Consulting Services</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('procurement_type')" class="mt-2" />
                                </div>
                            </div>

                            <div class="mb-4">
                                <x-input-label for="procurement_method" value="Procurement Method" />
                                <select id="procurement_method" name="procurement_method" class="mt-1 block w-full border-gray-300 rounded-md">
                                    <option value="">Select Method</option>
                                    <option value="small_value_procurement" {{ old('procurement_method', $purchaseRequest->procurement_method) == 'small_value_procurement' ? 'selected' : '' }}>Small Value Procurement</option>
                                    <option value="public_bidding" {{ old('procurement_method', $purchaseRequest->procurement_method) == 'public_bidding' ? 'selected' : '' }}>Public Bidding</option>
                                    <option value="direct_contracting" {{ old('procurement_method', $purchaseRequest->procurement_method) == 'direct_contracting' ? 'selected' : '' }}>Direct Contracting</option>
                                    <option value="negotiated_procurement" {{ old('procurement_method', $purchaseRequest->procurement_method) == 'negotiated_procurement' ? 'selected' : '' }}>Negotiated Procurement</option>
                                </select>
                                <x-input-error :messages="$errors->get('procurement_method')" class="mt-2" />
                            </div>
                        </div>

                        <div class="border-t pt-4">
                            <h3 class="text-lg font-semibold mb-4">Budget Approval</h3>

                            <div>
                                <x-input-label for="approved_budget_total" value="Approved Budget Total" />
                                <x-text-input id="approved_budget_total" name="approved_budget_total" type="number" step="0.01" class="mt-1 block w-full"
                                    value="{{ old('approved_budget_total', number_format((float)$purchaseRequest->estimated_total,2,'.','')) }}" required />
                                <x-input-error :messages="$errors->get('approved_budget_total')" class="mt-2" />
                            </div>

                            <div class="mt-4">
                                <x-input-label for="comments" value="Comments" />
                                <textarea id="comments" name="comments" rows="3" class="mt-1 block w-full border-gray-300 rounded-md">{{ old('comments') }}</textarea>
                                <x-input-error :messages="$errors->get('comments')" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('budget.purchase-requests.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md">Cancel</a>
                            <x-primary-button>Approve & Forward to CEO</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>