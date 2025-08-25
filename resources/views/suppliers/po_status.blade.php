@section('title', 'Supplier PO Status')

<x-guest-layout>
	<div class="max-w-5xl mx-auto py-12">
		<div class="bg-white shadow rounded-lg p-6">
			<h1 class="text-2xl font-semibold mb-4">Purchase Order Status</h1>
			<form method="GET" class="flex items-end space-x-3 mb-4">
				<div class="flex-1">
					<label class="text-sm text-gray-600">Supplier Email</label>
					<input type="email" name="supplier_email" value="{{ $email }}" placeholder="supplier@example.com" class="mt-1 block w-full border-gray-300 rounded-md" required />
				</div>
				<x-primary-button>View</x-primary-button>
			</form>

			@if($email && !$supplier)
				<div class="mb-4 p-3 rounded-md bg-red-50 text-red-700">No supplier found for that email.</div>
			@endif

			@if($supplier)
				<div class="mb-3 text-sm text-gray-600">Showing POs for: <span class="font-medium">{{ $supplier->business_name }}</span></div>
				<div class="overflow-x-auto">
					<table class="min-w-full divide-y divide-gray-200">
						<thead class="bg-gray-50">
							<tr>
								<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PO #</th>
								<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
								<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PR #</th>
								<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
								<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
							</tr>
						</thead>
						<tbody class="bg-white divide-y divide-gray-200">
							@forelse($orders as $po)
							<tr>
								<td class="px-4 py-2 font-mono">{{ $po->po_number }}</td>
								<td class="px-4 py-2">{{ optional($po->po_date)->format('Y-m-d') }}</td>
								<td class="px-4 py-2">{{ $po->purchaseRequest?->pr_number }}</td>
								<td class="px-4 py-2">₱{{ number_format((float)$po->total_amount, 2) }}</td>
								<td class="px-4 py-2 capitalize">{{ str_replace('_',' ',$po->status) }}</td>
							</tr>
							@empty
							<tr>
								<td colspan="5" class="px-4 py-6 text-center text-gray-500">No purchase orders found.</td>
							</tr>
							@endforelse
						</tbody>
					</table>
				</div>
				<div class="mt-3">{{ $orders->links() }}</div>
			@endif
		</div>
	</div>
</x-guest-layout>


