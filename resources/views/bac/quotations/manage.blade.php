@section('title', 'BAC - Manage Quotations')

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('Manage Quotations: ') . $purchaseRequest->pr_number }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-6">
                    @if(session('status'))
                        <div class="mb-4 p-3 rounded-md bg-green-50 text-green-700">{{ session('status') }}</div>
                    @endif

                    <div class="text-sm text-gray-600">Purpose</div>
                    <div class="font-medium mb-4">{{ $purchaseRequest->purpose }}</div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="font-semibold mb-2">Record Supplier Quotation</h3>
                            <form action="{{ route('bac.quotations.store', $purchaseRequest) }}" method="POST" class="space-y-3">
                                @csrf
                                <div>
                                    <label class="text-sm text-gray-600">Supplier</label>
                                    <select name="supplier_id" class="w-full border-gray-300 rounded-md" required>
                                        @foreach($suppliers as $s)
                                            <option value="{{ $s->id }}">{{ $s->business_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-sm text-gray-600">Quotation Date</label>
                                        <input type="date" name="quotation_date" class="w-full border-gray-300 rounded-md" required />
                                    </div>
                                    <div>
                                        <label class="text-sm text-gray-600">Validity Date</label>
                                        <input type="date" name="validity_date" class="w-full border-gray-300 rounded-md" required />
                                    </div>
                                </div>
                                <div>
                                    <label class="text-sm text-gray-600">Total Amount</label>
                                    <input type="number" step="0.01" name="total_amount" class="w-full border-gray-300 rounded-md" required />
                                </div>
                                <x-primary-button>Save Quotation</x-primary-button>
                            </form>
                        </div>
                        <div>
                            <h3 class="font-semibold mb-2">Submitted Quotations</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th class="px-4 py-2"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @forelse($quotations as $q)
                                        <tr>
                                            <td class="px-4 py-2">{{ $q->supplier?->business_name }}</td>
                                            <td class="px-4 py-2">{{ number_format((float)$q->total_amount, 2) }}</td>
                                            <td class="px-4 py-2 capitalize">{{ str_replace('_', ' ', $q->bac_status) }}</td>
                                            <td class="px-4 py-2 text-right">
                                                <form action="{{ route('bac.quotations.evaluate', $q) }}" method="POST" class="inline-flex items-center space-x-2">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="number" step="0.01" name="technical_score" placeholder="Tech" class="w-20 border-gray-300 rounded-md" />
                                                    <input type="number" step="0.01" name="financial_score" placeholder="Fin" class="w-20 border-gray-300 rounded-md" />
                                                    <select name="bac_status" class="border-gray-300 rounded-md">
                                                        <option value="compliant">Compliant</option>
                                                        <option value="non_compliant">Non-compliant</option>
                                                        <option value="lowest_bidder">Lowest Bidder</option>
                                                    </select>
                                                    <input type="text" name="bac_remarks" placeholder="Remarks" class="border-gray-300 rounded-md" />
                                                    <x-primary-button>Save</x-primary-button>
                                                </form>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="4" class="px-4 py-6 text-center text-gray-500">No quotations yet.</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('bac.quotations.finalize', $purchaseRequest) }}" method="POST" class="mt-6 border-t pt-4">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                            <div>
                                <label class="text-sm text-gray-600">Winning Quotation</label>
                                <select name="winning_quotation_id" class="w-full border-gray-300 rounded-md">
                                    <option value="">Select winner (optional)</option>
                                    @foreach($quotations as $q)
                                        <option value="{{ $q->id }}">{{ $q->supplier?->business_name }} - ₱{{ number_format((float)$q->total_amount, 2) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-2 text-right">
                                <x-primary-button>Finalize Abstract</x-primary-button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>


