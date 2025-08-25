@section('title', 'Supply - Create Purchase Order')

<x-app-layout>
	<x-slot name="header">
		<h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('Create Purchase Order for ') . $purchaseRequest->pr_number }}</h2>
	</x-slot>

	<div class="py-8">
		<div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
			<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
				<div class="p-6 text-gray-900">
					@if($winningQuotation)
						<div class="mb-4 p-3 rounded-md bg-green-50 text-green-700">
							Winning quotation detected: {{ $winningQuotation->supplier?->business_name }} (₱{{ number_format((float)$winningQuotation->total_amount, 2) }})
						</div>
					@endif

					<form action="{{ route('supply.purchase-orders.store', $purchaseRequest) }}" method="POST" class="space-y-6">
						@csrf
						<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
							<div>
								<label class="text-sm text-gray-600">PO Date</label>
								<input type="date" name="po_date" class="mt-1 block w-full border-gray-300 rounded-md" required />
							</div>
							<div>
								<label class="text-sm text-gray-600">Supplier</label>
								<select name="supplier_id" class="mt-1 block w-full border-gray-300 rounded-md" required>
									@if($winningQuotation)
										<option value="{{ $winningQuotation->supplier_id }}">{{ $winningQuotation->supplier?->business_name }}</option>
									@endif
									@foreach($suppliers as $s)
										<option value="{{ $s->id }}">{{ $s->business_name }}</option>
									@endforeach
								</select>
							</div>
						</div>

						<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
							<div>
								<label class="text-sm text-gray-600">Total Amount</label>
								<input type="number" step="0.01" name="total_amount" class="mt-1 block w-full border-gray-300 rounded-md" value="{{ $winningQuotation?->total_amount }}" required />
							</div>
							<div>
								<label class="text-sm text-gray-600">Delivery Date Required</label>
								<input type="date" name="delivery_date_required" class="mt-1 block w-full border-gray-300 rounded-md" required />
							</div>
						</div>

						<div>
							<label class="text-sm text-gray-600">Delivery Address</label>
							<textarea name="delivery_address" rows="2" class="mt-1 block w-full border-gray-300 rounded-md" required>{{ $purchaseRequest->department?->name }} Campus</textarea>
						</div>

						<div>
							<label class="text-sm text-gray-600">Terms and Conditions</label>
							<textarea name="terms_and_conditions" rows="4" class="mt-1 block w-full border-gray-300 rounded-md" required>Standard government procurement terms under RA 9184 apply.</textarea>
						</div>

						<div>
							<label class="text-sm text-gray-600">Special Instructions (optional)</label>
							<textarea name="special_instructions" rows="2" class="mt-1 block w-full border-gray-300 rounded-md"></textarea>
						</div>

						<input type="hidden" name="quotation_id" value="{{ $winningQuotation?->id }}" />

						<div class="flex justify-end space-x-3">
							<a href="{{ route('supply.purchase-requests.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md">Cancel</a>
							<x-primary-button>Create PO</x-primary-button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</x-app-layout>


