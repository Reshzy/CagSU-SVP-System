@section('title', 'Supply - Inventory Receipt Details')

<x-app-layout>
	<x-slot name="header">
		<h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('Receipt for PO: ') . $receipt->purchaseOrder?->po_number }}</h2>
	</x-slot>

<div class="py-8">
	<div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
		<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
			<div class="p-6 text-gray-900 space-y-6">
				<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
					<div>
						<div class="text-sm text-gray-600">Received Date</div>
						<div class="font-medium">{{ optional($receipt->received_date)->format('Y-m-d') }}</div>
					</div>
					<div>
						<div class="text-sm text-gray-600">Reference No.</div>
						<div class="font-medium">{{ $receipt->reference_no ?? '—' }}</div>
					</div>
				</div>

				<div>
					<div class="text-sm text-gray-600 mb-1">Items</div>
					<div class="overflow-x-auto">
						<table class="min-w-full divide-y divide-gray-200">
							<thead class="bg-gray-50">
								<tr>
									<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
									<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit</th>
									<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
									<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
									<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
								</tr>
							</thead>
							<tbody class="bg-white divide-y divide-gray-200">
								@foreach($receipt->items as $it)
								<tr>
									<td class="px-4 py-2">{{ $it->description }}</td>
									<td class="px-4 py-2">{{ $it->unit_of_measure ?? '—' }}</td>
									<td class="px-4 py-2">{{ $it->quantity }}</td>
									<td class="px-4 py-2">{{ $it->unit_price !== null ? number_format((float)$it->unit_price,2) : '—' }}</td>
									<td class="px-4 py-2">{{ $it->total_price !== null ? number_format((float)$it->total_price,2) : '—' }}</td>
								</tr>
								@endforeach
							</tbody>
						</table>
					</div>
				</div>

				<div class="flex justify-end">
					<a href="{{ route('supply.inventory-receipts.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md">Back</a>
				</div>
			</div>
		</div>
	</div>
</div>

</x-app-layout>


