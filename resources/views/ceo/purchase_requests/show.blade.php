@section('title', 'CEO - PR Details')

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('PR Details: ') . $purchaseRequest->pr_number }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <div class="text-sm text-gray-600">Requester</div>
                            <div class="font-medium">{{ $purchaseRequest->requester?->name }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600">Department</div>
                            <div class="font-medium">{{ $purchaseRequest->department?->name }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600">Purpose</div>
                            <div class="font-medium">{{ $purchaseRequest->purpose }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600">Estimated Total</div>
                            <div class="font-medium">{{ number_format((float)$purchaseRequest->estimated_total, 2) }}</div>
                        </div>
                    </div>

                    <div>
                        <div class="text-sm text-gray-600">Items</div>
                        <ul class="list-disc ml-5">
                            @foreach($purchaseRequest->items as $it)
                                <li>{{ $it->item_name }} ({{ $it->quantity_requested }} x {{ number_format((float)$it->estimated_unit_cost,2) }})</li>
                            @endforeach
                        </ul>
                    </div>

                    <div>
                        <div class="text-sm text-gray-600">Attachments</div>
                        @forelse($purchaseRequest->documents as $doc)
                            <div class="text-sm"><a class="text-cagsu-maroon" href="{{ route('files.show', $doc) }}" target="_blank">{{ $doc->file_name }}</a></div>
                        @empty
                            <div class="text-sm text-gray-500">No attachments</div>
                        @endforelse
                    </div>

                    <form action="{{ route('ceo.purchase-requests.update', $purchaseRequest) }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <div>
                            <x-input-label for="comments" value="Comments" />
                            <textarea id="comments" name="comments" rows="3" class="mt-1 block w-full border-gray-300 rounded-md"></textarea>
                        </div>
                        <div>
                            <x-input-label for="rejection_reason" value="Rejection Reason (if rejecting)" />
                            <textarea id="rejection_reason" name="rejection_reason" rows="2" class="mt-1 block w-full border-gray-300 rounded-md"></textarea>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('ceo.purchase-requests.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md">Back</a>
                            <button name="decision" value="reject" class="px-4 py-2 bg-red-600 text-white rounded-md">Reject</button>
                            <button name="decision" value="approve" class="px-4 py-2 bg-green-600 text-white rounded-md">Approve</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>


