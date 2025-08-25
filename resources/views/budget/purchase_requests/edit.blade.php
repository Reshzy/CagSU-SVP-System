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
                        <div>
                            <x-input-label for="approved_budget_total" value="Approved Budget Total" />
                            <x-text-input id="approved_budget_total" name="approved_budget_total" type="number" step="0.01" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('approved_budget_total')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="comments" value="Comments" />
                            <textarea id="comments" name="comments" rows="3" class="mt-1 block w-full border-gray-300 rounded-md"></textarea>
                            <x-input-error :messages="$errors->get('comments')" class="mt-2" />
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


