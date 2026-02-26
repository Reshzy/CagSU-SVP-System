@section('title', 'Supply - Purchase Order Details')

<x-app-layout>
	<x-slot name="header">
		<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
			<div>
				<!-- <h2 class="font-semibold text-2xl text-gray-800 leading-tight dark:text-gray-100"> -->
				<h2 class="font-semibold text-2xl text-gray-800 leading-tight">
					{{ __('PO Details: ') . $purchaseOrder->po_number }}
				</h2>
				<!-- <p class="mt-1 text-sm text-gray-600 dark:text-gray-300"> -->
				<p class="mt-1 text-sm text-gray-600">
					PR: {{ $purchaseOrder->purchaseRequest?->pr_number ?? '—' }}
				</p>
			</div>

			<div class="flex flex-wrap items-center gap-2">
				<a href="{{ route('supply.purchase-orders.edit', $purchaseOrder) }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
					Edit PO
				</a>
				<a href="{{ route('supply.purchase-orders.export', $purchaseOrder) }}" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md font-semibold text-sm">
					📥 Export to Excel
				</a>
			</div>
		</div>
	</x-slot>

	@php
		$status = $purchaseOrder->status;
		$statusClasses = match ($status) {
			'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100',
			'delivered' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100',
			'sent_to_supplier' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100',
			'acknowledged_by_supplier' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-100',
			default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-100',
		};

		$poItems = $purchaseOrder->items;
		$hasPoItems = $poItems->isNotEmpty();

		$quotationItems = $purchaseOrder->quotation?->quotationItems ?? collect();
		$hasQuotationItems = $quotationItems->isNotEmpty();

		$prItems = $purchaseOrder->purchaseRequest?->items ?? collect();
		if ($purchaseOrder->pr_item_group_id && $prItems->isNotEmpty()) {
			$prItems = $prItems->where('pr_item_group_id', $purchaseOrder->pr_item_group_id);
		}
		$hasPrItems = $prItems->isNotEmpty();
	@endphp

	<div class="py-8">
		<div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
			<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg dark:bg-gray-800">
				<div class="p-6 text-gray-900 space-y-8 dark:text-gray-100">
					<!-- PO Overview -->
					<div class="border-b pb-4">
						<h3 class="text-lg font-semibold mb-3">Purchase Order Information</h3>
						<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
							<div>
								<div class="text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-400">PO Number</div>
								<div class="mt-1 font-semibold text-gray-900 dark:text-gray-100">{{ $purchaseOrder->po_number }}</div>
							</div>
							<div>
								<div class="text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-400">PO Date</div>
								<div class="mt-1 font-medium">{{ optional($purchaseOrder->po_date)->format('Y-m-d') ?? '—' }}</div>
							</div>
							<div class="flex flex-col items-start md:items-end">
								<div class="text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-400">Status</div>
								<span class="mt-1 inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusClasses }}">
									{{ str_replace('_', ' ', ucfirst($status)) }}
								</span>
							</div>
						</div>

						<div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
							<div>
								<div class="text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-400">Supplier</div>
								<div class="mt-1 font-medium">
									{{ $purchaseOrder->supplier_name_override ?? $purchaseOrder->supplier?->business_name ?? '—' }}
								</div>
							</div>
							<div>
								<div class="text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-400">Total Amount</div>
								<div class="mt-1 font-semibold">
									{{ $purchaseOrder->total_amount !== null ? '₱' . number_format((float) $purchaseOrder->total_amount, 2) : '—' }}
								</div>
							</div>
							<div>
								<div class="text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-400">Delivery Date Required</div>
								<div class="mt-1">
									{{ optional($purchaseOrder->delivery_date_required)->format('Y-m-d') ?? '—' }}
								</div>
							</div>
						</div>
					</div>

					<!-- Delivery & Terms -->
					<div class="border-b pb-4 space-y-4">
						<div>
							<div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1 dark:text-gray-400">Delivery Address</div>
							<div class="whitespace-pre-wrap text-sm">{{ $purchaseOrder->delivery_address ?? '—' }}</div>
						</div>

						<div>
							<div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1 dark:text-gray-400">Terms and Conditions</div>
							<div class="whitespace-pre-wrap text-sm">{{ $purchaseOrder->terms_and_conditions ?? '—' }}</div>
						</div>

						@if($purchaseOrder->special_instructions)
							<div>
								<div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1 dark:text-gray-400">Special Instructions</div>
								<div class="whitespace-pre-wrap text-sm">{{ $purchaseOrder->special_instructions }}</div>
							</div>
						@endif
					</div>

					<!-- Line Items -->
					<div class="border-b pb-4">
						<div class="flex items-center justify-between mb-3">
							<h3 class="text-lg font-semibold">Line Items</h3>
							<p class="text-xs text-gray-500 dark:text-gray-400">
								@if ($hasPoItems)
									Showing items from this Purchase Order.
								@elseif ($hasQuotationItems)
									Showing items from the winning quotation.
								@elseif ($hasPrItems)
									Showing items from the linked Purchase Request.
								@else
									No items available for this Purchase Order.
								@endif
							</p>
						</div>

						@if ($hasPoItems || $hasQuotationItems || $hasPrItems)
							<div class="overflow-x-auto">
								<table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
									<thead class="bg-gray-50 dark:bg-gray-700">
										<tr>
											<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-300">#</th>
											<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-300">Description</th>
											<th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-300">Unit</th>
											<th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-300">Qty</th>
											<th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-300">Unit Price</th>
											<th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wide dark:text-gray-300">Total</th>
										</tr>
									</thead>
									<tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
										@if ($hasPoItems)
											@foreach ($poItems as $index => $item)
												@php
													$prItem = $item->purchaseRequestItem;
													$description = $prItem?->item_name ?? 'Item #' . $item->id;
													$unit = $prItem?->unit_of_measure ?? 'pcs';
												@endphp
												<tr>
													<td class="px-3 py-2 text-xs text-gray-500 dark:text-gray-400">{{ $index + 1 }}</td>
													<td class="px-3 py-2">{{ $description }}</td>
													<td class="px-3 py-2">{{ $unit }}</td>
													<td class="px-3 py-2 text-right font-mono">{{ number_format((float) $item->quantity, 0) }}</td>
													<td class="px-3 py-2 text-right font-mono">₱{{ number_format((float) $item->unit_price, 2) }}</td>
													<td class="px-3 py-2 text-right font-mono">₱{{ number_format((float) $item->total_price, 2) }}</td>
												</tr>
											@endforeach
										@elseif ($hasQuotationItems)
											@foreach ($quotationItems as $index => $qItem)
												@php
													$prItem = $qItem->purchaseRequestItem;
													$description = $prItem?->item_name ?? $qItem->item_name ?? 'Item #' . $qItem->id;
													$unit = $prItem?->unit_of_measure ?? $qItem->unit_of_measure ?? 'pcs';
													$quantity = $prItem?->quantity_requested ?? $qItem->quantity ?? 0;
													$unitPrice = $qItem->unit_price ?? $prItem?->awarded_unit_price ?? $prItem?->estimated_unit_cost ?? 0;
													$total = $qItem->total_price ?? $prItem?->awarded_total_price ?? $prItem?->estimated_total_cost ?? ($quantity * $unitPrice);
												@endphp
												<tr>
													<td class="px-3 py-2 text-xs text-gray-500 dark:text-gray-400">{{ $index + 1 }}</td>
													<td class="px-3 py-2">{{ $description }}</td>
													<td class="px-3 py-2">{{ $unit }}</td>
													<td class="px-3 py-2 text-right font-mono">{{ number_format((float) $quantity, 0) }}</td>
													<td class="px-3 py-2 text-right font-mono">₱{{ number_format((float) $unitPrice, 2) }}</td>
													<td class="px-3 py-2 text-right font-mono">₱{{ number_format((float) $total, 2) }}</td>
												</tr>
											@endforeach
										@elseif ($hasPrItems)
											@foreach ($prItems as $index => $prItem)
												@php
													$description = $prItem->item_name ?? 'Item #' . $prItem->id;
													$unit = $prItem->unit_of_measure ?? 'pcs';
													$quantity = $prItem->quantity_requested ?? 0;
													$unitPrice = $prItem->awarded_unit_price ?? $prItem->estimated_unit_cost ?? 0;
													$total = $prItem->awarded_total_price ?? $prItem->estimated_total_cost ?? ($quantity * $unitPrice);
												@endphp
												<tr>
													<td class="px-3 py-2 text-xs text-gray-500 dark:text-gray-400">{{ $index + 1 }}</td>
													<td class="px-3 py-2">{{ $description }}</td>
													<td class="px-3 py-2">{{ $unit }}</td>
													<td class="px-3 py-2 text-right font-mono">{{ number_format((float) $quantity, 0) }}</td>
													<td class="px-3 py-2 text-right font-mono">₱{{ number_format((float) $unitPrice, 2) }}</td>
													<td class="px-3 py-2 text-right font-mono">₱{{ number_format((float) $total, 2) }}</td>
												</tr>
											@endforeach
										@endif
									</tbody>
								</table>
							</div>
						@else
							<p class="text-sm text-gray-500 dark:text-gray-400">
								No items are linked to this Purchase Order yet.
							</p>
						@endif
					</div>

					<!-- Documents & Actions -->
					<div class="space-y-6">
						<div class="border-b pb-4">
							<h3 class="text-lg font-semibold mb-3">Completion & Documents</h3>
							<div class="space-y-4">
								<div>
									<div class="text-sm text-gray-600 mb-2 dark:text-gray-300">Inspection & Acceptance Report</div>
									<form action="{{ route('supply.purchase-orders.update', $purchaseOrder) }}" method="POST" enctype="multipart/form-data" class="flex flex-col items-start gap-3 sm:flex-row sm:items-center">
										@csrf
										@method('PUT')
										<input type="hidden" name="action" value="complete" />
										<input type="file" name="inspection_file" class="border-gray-300 rounded-md text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100" />
										<button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
											Upload &amp; Complete
										</button>
									</form>
								</div>

								<div>
									<a href="{{ route('supply.inventory-receipts.create', $purchaseOrder) }}" class="inline-flex items-center px-3 py-2 bg-cagsu-maroon text-white rounded-md text-sm font-medium hover:bg-cagsu-orange transition">
										Record Inventory Receipt
									</a>
								</div>
							</div>
						</div>

						<div class="flex flex-col items-stretch gap-3 sm:flex-row sm:items-center sm:justify-between">
							<form action="{{ route('supply.purchase-orders.update', $purchaseOrder) }}" method="POST" class="inline-flex items-center gap-2">
								@csrf
								@method('PUT')
								<label for="po-action" class="text-sm text-gray-700 dark:text-gray-200">Update status:</label>
								<select id="po-action" name="action" class="border-gray-300 rounded-md text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
									<option value="send_to_supplier">Send to Supplier</option>
									<option value="acknowledge">Acknowledge</option>
									<option value="mark_delivered">Mark Delivered</option>
									<option value="complete">Complete</option>
								</select>
								<button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
									Apply
								</button>
							</form>

							<a href="{{ route('supply.purchase-orders.index') }}" id="back-to-po-list" class="inline-flex justify-center px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
								Back to list
							</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</x-app-layout>

@push('scripts')
<script>
	document.addEventListener('DOMContentLoaded', function () {
		var link = document.getElementById('back-to-po-list');
		if (!link) return;
		var saved = null;
		try { saved = JSON.parse(localStorage.getItem('po_filters') || '{}'); } catch (e) { return; }
		var base = link.getAttribute('href');
		var params = new URLSearchParams();
		if (saved.poNumberSearch) params.set('po', saved.poNumberSearch);
		if (saved.supplierFilter) params.set('supplier', saved.supplierFilter);
		if (saved.prNumberFilter) params.set('pr', saved.prNumberFilter);
		if (saved.statusFilter) params.set('statusFilter', saved.statusFilter);
		if (params.toString()) link.setAttribute('href', base + (base.indexOf('?') >= 0 ? '&' : '?') + params.toString());
	});
</script>
@endpush


