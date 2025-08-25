@section('title', 'Supply - Purchase Orders')

<x-app-layout>
	<x-slot name="header">
		<h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('Purchase Orders') }}</h2>
	</x-slot>

	<div class="py-8">
		<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
				<div class="p-6 text-gray-900">
					<div class="overflow-x-auto">
						<table class="min-w-full divide-y divide-gray-200">
							<thead class="bg-gray-50">
								<tr>
									<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PO #</th>
									<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PO Date</th>
									<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PR #</th>
									<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
									<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
									<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
									<th class="px-4 py-2"></th>
								</tr>
							</thead>
							<tbody class="bg-white divide-y divide-gray-200">
								@forelse($orders as $po)
								<tr>
									<td class="px-4 py-2 font-mono">{{ $po->po_number }}</td>
									<td class="px-4 py-2">{{ optional($po->po_date)->format('Y-m-d') }}</td>
									<td class="px-4 py-2">{{ $po->purchaseRequest?->pr_number }}</td>
									<td class="px-4 py-2">{{ $po->supplier?->business_name }}</td>
									<td class="px-4 py-2">{{ number_format((float)$po->total_amount, 2) }}</td>
									<td class="px-4 py-2 capitalize">{{ str_replace('_', ' ', $po->status) }}</td>
									<td class="px-4 py-2 text-right">
										<a href="{{ route('supply.purchase-orders.show', $po) }}" class="px-3 py-2 bg-gray-100 text-gray-700 rounded-md">Details</a>
									</td>
								</tr>
								@empty
								<tr>
									<td colspan="7" class="px-4 py-6 text-center text-gray-500">No purchase orders.</td>
								</tr>
								@endforelse
							</tbody>
						</table>
					</div>

					<div class="mt-4">{{ $orders->links() }}</div>
				</div>
			</div>
		</div>
	</div>
</x-app-layout>


