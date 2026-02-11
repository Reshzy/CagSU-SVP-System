@section('title', 'Supply - PO Preview')

<x-app-layout>
	<x-slot name="header">
		<h2 class="font-semibold text-2xl text-gray-800 leading-tight">
			{{ __('Purchase Order Preview for ') . $purchaseRequest->pr_number }}
			@if(isset($itemGroup))
				<span class="text-lg text-gray-600"> - {{ $itemGroup->group_code }}: {{ $itemGroup->group_name }}</span>
			@endif
		</h2>
	</x-slot>

	<div class="py-8">
		<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
				<div class="p-6 text-gray-900">
					<div class="mb-6">
						<div class="flex items-start gap-3 p-4 rounded-md bg-blue-50 border border-blue-200">
							<svg class="w-6 h-6 text-blue-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
								<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
							</svg>
							<div class="flex-1">
								<h4 class="text-sm font-semibold text-blue-900 mb-1">Multiple Winning Suppliers Detected</h4>
								<p class="text-sm text-blue-800">
									Based on AOQ results, <strong>{{ $winningItemsBySupplier->count() }} Purchase Orders</strong> will be created for different suppliers.
								</p>
							</div>
						</div>
					</div>

					@foreach($winningItemsBySupplier as $index => $supplierData)
						<div class="mb-6 border border-gray-200 rounded-lg overflow-hidden">
							<div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
								<h3 class="text-lg font-semibold text-gray-900">
									Purchase Order {{ $index + 1 }}
								</h3>
							</div>

							<div class="p-6">
								<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
									<div>
										<label class="block text-sm font-medium text-gray-600 mb-1">Supplier</label>
										<div class="text-base font-semibold text-gray-900">
											{{ $supplierData['supplier']->business_name }}
										</div>
									</div>
									<div>
										<label class="block text-sm font-medium text-gray-600 mb-1">Address</label>
										<div class="text-base text-gray-900">
											{{ $supplierData['supplier']->address ?? 'N/A' }}
										</div>
									</div>
									@if($supplierData['supplier']->tin)
										<div>
											<label class="block text-sm font-medium text-gray-600 mb-1">TIN</label>
											<div class="text-base text-gray-900">
												{{ $supplierData['supplier']->tin }}
											</div>
										</div>
									@endif
								</div>

								<div class="mb-4">
									<h4 class="text-sm font-semibold text-gray-700 mb-3">Items Won:</h4>
									<div class="overflow-x-auto">
										<table class="min-w-full divide-y divide-gray-200">
											<thead class="bg-gray-50">
												<tr>
													<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
													<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Description</th>
													<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit</th>
													<th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
													<th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
													<th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
												</tr>
											</thead>
											<tbody class="bg-white divide-y divide-gray-200">
												@foreach($supplierData['items'] as $itemIndex => $item)
													<tr>
														<td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $itemIndex + 1 }}</td>
														<td class="px-4 py-3 text-sm text-gray-900">{{ $item['pr_item']->item_name }}</td>
														<td class="px-4 py-3 text-sm text-gray-900">{{ $item['pr_item']->unit_of_measure }}</td>
														<td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right">{{ $item['quantity'] }}</td>
														<td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right">₱{{ number_format($item['unit_price'], 2) }}</td>
														<td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 text-right">₱{{ number_format($item['total_price'], 2) }}</td>
													</tr>
												@endforeach
											</tbody>
											<tfoot class="bg-gray-50">
												<tr>
													<td colspan="5" class="px-4 py-3 text-sm font-semibold text-gray-900 text-right">Estimated Total:</td>
													<td class="px-4 py-3 text-sm font-bold text-gray-900 text-right">₱{{ number_format($supplierData['total_amount'], 2) }}</td>
												</tr>
											</tfoot>
										</table>
									</div>
								</div>
							</div>
						</div>
					@endforeach

					<div class="mt-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
						<div class="flex justify-between items-center">
							<div>
								<span class="text-sm font-medium text-gray-600">Combined Total:</span>
								<span class="ml-2 text-lg font-bold text-gray-900">₱{{ number_format($winningItemsBySupplier->sum('total_amount'), 2) }}</span>
							</div>
							<div>
								<span class="text-sm text-gray-600">{{ $winningItemsBySupplier->count() }} POs | {{ $winningItemsBySupplier->sum('item_count') }} Items</span>
							</div>
						</div>
					</div>

					<div class="flex justify-end gap-3 mt-6 pt-4 border-t">
						<a href="{{ route('supply.purchase-requests.show', $purchaseRequest) }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition">
							Cancel
						</a>
						<a href="{{ route('supply.purchase-orders.batch-create', ['purchaseRequest' => $purchaseRequest, 'group' => $itemGroup?->id]) }}" class="px-6 py-2 bg-cagsu-maroon text-white rounded-md hover:bg-cagsu-orange transition font-medium">
							Proceed to Create POs
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
</x-app-layout>
