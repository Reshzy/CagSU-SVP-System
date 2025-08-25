@section('title', 'Supply - Purchase Order Details')

<x-app-layout>
	<x-slot name="header">
		<h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('PO Details: ') . $purchaseOrder->po_number }}</h2>
	</x-slot>

	<div class="py-8">
		<div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
			<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
				<div class="p-6 text-gray-900 space-y-6">
					<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
						<div>
							<div class="text-sm text-gray-600">PO Number</div>
							<div class="font-medium">{{ $purchaseOrder->po_number }}</div>
						</div>
						<div>
							<div class="text-sm text-gray-600">PO Date</div>
							<div class="font-medium">{{ optional($purchaseOrder->po_date)->format('Y-m-d') }}</div>
						</div>
						<div>
							<div class="text-sm text-gray-600">Supplier</div>
							<div class="font-medium">{{ $purchaseOrder->supplier?->business_name }}</div>
						</div>
						<div>
							<div class="text-sm text-gray-600">PR Number</div>
							<div class="font-medium">{{ $purchaseOrder->purchaseRequest?->pr_number }}</div>
						</div>
						<div>
							<div class="text-sm text-gray-600">Total Amount</div>
							<div class="font-medium">₱{{ number_format((float)$purchaseOrder->total_amount, 2) }}</div>
						</div>
						<div>
							<div class="text-sm text-gray-600">Status</div>
							<div class="font-medium capitalize">{{ str_replace('_', ' ', $purchaseOrder->status) }}</div>
						</div>
					</div>

					<div>
						<div class="text-sm text-gray-600">Delivery Address</div>
						<div class="whitespace-pre-wrap">{{ $purchaseOrder->delivery_address }}</div>
					</div>

					<div>
						<div class="text-sm text-gray-600">Terms and Conditions</div>
						<div class="whitespace-pre-wrap">{{ $purchaseOrder->terms_and_conditions }}</div>
					</div>

					<div class="flex justify-end">
						<a href="{{ route('supply.purchase-orders.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md">Back</a>
					</div>
				</div>
			</div>
		</div>
	</div>
</x-app-layout>


