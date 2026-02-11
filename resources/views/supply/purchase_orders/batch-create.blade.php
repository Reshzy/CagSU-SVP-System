@section('title', 'Supply - Create Purchase Orders')

<x-app-layout>
	<x-slot name="header">
		<h2 class="font-semibold text-2xl text-gray-800 leading-tight">
			{{ __('Create Purchase Orders for ') . $purchaseRequest->pr_number }}
			@if(isset($itemGroup))
				<span class="text-lg text-gray-600"> - {{ $itemGroup->group_code }}: {{ $itemGroup->group_name }}</span>
			@endif
		</h2>
	</x-slot>

	<div class="py-8">
		<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
				<div class="p-6 text-gray-900">
					@if(!$ceoSignatory || !$chiefAccountantSignatory)
						<div class="mb-4 p-3 rounded-md bg-yellow-50 text-yellow-700">
							<strong>Warning:</strong> PO Signatories not configured. Please configure them in 
							<a href="{{ route('supply.po-signatories.index') }}" class="underline font-semibold">PO Signatories Management</a>.
						</div>
					@endif

					<form action="{{ route('supply.purchase-orders.batch-store', $purchaseRequest) }}" method="POST" class="space-y-6" id="batchPoForm">
						@csrf
						@if(isset($itemGroup))
							<input type="hidden" name="pr_item_group_id" value="{{ $itemGroup->id }}" />
						@endif

						<div class="border-b pb-4 mb-6">
							<div class="flex items-center gap-3">
								<input type="checkbox" id="applyToAll" class="rounded border-gray-300 text-cagsu-maroon focus:ring-cagsu-maroon">
								<label for="applyToAll" class="text-sm font-medium text-gray-700 cursor-pointer">
									Apply same financial and delivery details to all POs
								</label>
							</div>
						</div>

						@foreach($winningItemsBySupplier as $index => $supplierData)
							<div class="border border-gray-300 rounded-lg overflow-hidden mb-6" data-po-index="{{ $index }}">
								<div class="bg-gray-100 px-6 py-4 border-b border-gray-300">
									<h3 class="text-lg font-semibold text-gray-900">
										PO #{{ $index + 1 }} - {{ $supplierData['supplier']->business_name }}
									</h3>
									<p class="text-sm text-gray-600 mt-1">
										{{ $supplierData['item_count'] }} item(s) | Total: ₱{{ number_format($supplierData['total_amount'], 2) }}
									</p>
								</div>

								<div class="p-6 space-y-6">
									<input type="hidden" name="purchase_orders[{{ $index }}][supplier_id]" value="{{ $supplierData['supplier']->id }}">
									<input type="hidden" name="purchase_orders[{{ $index }}][quotation_id]" value="{{ $supplierData['quotation']->id }}">
									<input type="hidden" name="purchase_orders[{{ $index }}][total_amount]" value="{{ $supplierData['total_amount'] }}">

									@foreach($supplierData['items'] as $itemIndex => $item)
										<input type="hidden" name="purchase_orders[{{ $index }}][items][{{ $itemIndex }}][purchase_request_item_id]" value="{{ $item['pr_item']->id }}">
										<input type="hidden" name="purchase_orders[{{ $index }}][items][{{ $itemIndex }}][quotation_item_id]" value="{{ $item['quotation_item']->id }}">
										<input type="hidden" name="purchase_orders[{{ $index }}][items][{{ $itemIndex }}][quantity]" value="{{ $item['quantity'] }}">
										<input type="hidden" name="purchase_orders[{{ $index }}][items][{{ $itemIndex }}][unit_price]" value="{{ $item['unit_price'] }}">
										<input type="hidden" name="purchase_orders[{{ $index }}][items][{{ $itemIndex }}][total_price]" value="{{ $item['total_price'] }}">
									@endforeach

									<div class="bg-gray-50 p-4 rounded-md">
										<h4 class="text-sm font-semibold text-gray-700 mb-2">Supplier Information</h4>
										<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
											<div>
												<label class="block text-sm font-medium text-gray-600">Supplier Name</label>
												<div class="mt-1 text-base text-gray-800">{{ $supplierData['supplier']->business_name }}</div>
											</div>
											<div>
												<label class="block text-sm font-medium text-gray-600">TIN (optional)</label>
												<input 
													type="text" 
													name="purchase_orders[{{ $index }}][tin]" 
													class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
													value="{{ old('purchase_orders.'.$index.'.tin', $supplierData['supplier']->tin) }}"
													placeholder="Enter TIN if available"
												/>
											</div>
										</div>
									</div>

									<div>
										<h4 class="text-sm font-semibold text-gray-700 mb-3">Financial Details</h4>
										<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
											<div>
												<label class="block text-sm font-medium text-gray-600">Funds Cluster <span class="text-red-500">*</span></label>
												<input 
													type="text" 
													name="purchase_orders[{{ $index }}][funds_cluster]" 
													class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm funds-cluster-input"
													value="{{ old('purchase_orders.'.$index.'.funds_cluster') }}"
													required 
												/>
												@error('purchase_orders.'.$index.'.funds_cluster')
													<p class="mt-1 text-sm text-red-600">{{ $message }}</p>
												@enderror
											</div>
											<div>
												<label class="block text-sm font-medium text-gray-600">Funds Available <span class="text-red-500">*</span></label>
												<input 
													type="number" 
													step="0.01" 
													name="purchase_orders[{{ $index }}][funds_available]" 
													class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm funds-available-input"
													value="{{ old('purchase_orders.'.$index.'.funds_available') }}"
													required 
												/>
												@error('purchase_orders.'.$index.'.funds_available')
													<p class="mt-1 text-sm text-red-600">{{ $message }}</p>
												@enderror
											</div>
											<div>
												<label class="block text-sm font-medium text-gray-600">ORS/BURS No. <span class="text-red-500">*</span></label>
												<input 
													type="text" 
													name="purchase_orders[{{ $index }}][ors_burs_no]" 
													class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm ors-burs-no-input"
													value="{{ old('purchase_orders.'.$index.'.ors_burs_no') }}"
													required 
												/>
												@error('purchase_orders.'.$index.'.ors_burs_no')
													<p class="mt-1 text-sm text-red-600">{{ $message }}</p>
												@enderror
											</div>
											<div>
												<label class="block text-sm font-medium text-gray-600">Date of ORS/BURS <span class="text-red-500">*</span></label>
												<input 
													type="date" 
													name="purchase_orders[{{ $index }}][ors_burs_date]" 
													class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm ors-burs-date-input"
													value="{{ old('purchase_orders.'.$index.'.ors_burs_date') }}"
													required 
												/>
												@error('purchase_orders.'.$index.'.ors_burs_date')
													<p class="mt-1 text-sm text-red-600">{{ $message }}</p>
												@enderror
											</div>
										</div>
									</div>

									<div>
										<h4 class="text-sm font-semibold text-gray-700 mb-3">Delivery Details</h4>
										<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
											<div>
												<label class="block text-sm font-medium text-gray-600">Delivery Address <span class="text-red-500">*</span></label>
												<textarea 
													name="purchase_orders[{{ $index }}][delivery_address]" 
													rows="2" 
													class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm delivery-address-input"
													required
												>{{ old('purchase_orders.'.$index.'.delivery_address', $purchaseRequest->department?->name . ' Campus') }}</textarea>
												@error('purchase_orders.'.$index.'.delivery_address')
													<p class="mt-1 text-sm text-red-600">{{ $message }}</p>
												@enderror
											</div>
											<div>
												<label class="block text-sm font-medium text-gray-600">Delivery Date Required <span class="text-red-500">*</span></label>
												<input 
													type="date" 
													name="purchase_orders[{{ $index }}][delivery_date_required]" 
													class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm delivery-date-input"
													value="{{ old('purchase_orders.'.$index.'.delivery_date_required') }}"
													required 
												/>
												@error('purchase_orders.'.$index.'.delivery_date_required')
													<p class="mt-1 text-sm text-red-600">{{ $message }}</p>
												@enderror
											</div>
										</div>
									</div>

									<div>
										<h4 class="text-sm font-semibold text-gray-700 mb-3">Additional Notes</h4>
										<div class="space-y-4">
											<div>
												<label class="block text-sm font-medium text-gray-600">Terms and Conditions <span class="text-red-500">*</span></label>
												<textarea 
													name="purchase_orders[{{ $index }}][terms_and_conditions]" 
													rows="3" 
													class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm terms-conditions-input"
													required
												>{{ old('purchase_orders.'.$index.'.terms_and_conditions', 'Standard government procurement terms under RA 9184 apply.') }}</textarea>
												@error('purchase_orders.'.$index.'.terms_and_conditions')
													<p class="mt-1 text-sm text-red-600">{{ $message }}</p>
												@enderror
											</div>

											<div>
												<label class="block text-sm font-medium text-gray-600">Special Instructions (optional)</label>
												<textarea 
													name="purchase_orders[{{ $index }}][special_instructions]" 
													rows="2" 
													class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm special-instructions-input"
												>{{ old('purchase_orders.'.$index.'.special_instructions') }}</textarea>
											</div>
										</div>
									</div>
								</div>
							</div>
						@endforeach

						<div class="border-t pt-6">
							<div class="bg-gray-50 p-4 rounded-lg mb-6">
								<h4 class="text-sm font-semibold text-gray-700 mb-3">Signatories</h4>
								<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
									<div>
										<label class="block text-sm font-medium text-gray-600">CEO</label>
										<div class="mt-1 text-base text-gray-800">
											{{ $ceoSignatory?->full_name ?? 'Not configured' }}
										</div>
									</div>
									<div>
										<label class="block text-sm font-medium text-gray-600">Chief Accountant</label>
										<div class="mt-1 text-base text-gray-800">
											{{ $chiefAccountantSignatory?->full_name ?? 'Not configured' }}
										</div>
									</div>
								</div>
							</div>
						</div>

						<div class="flex justify-end gap-3 pt-4">
							<a href="{{ route('supply.purchase-orders.preview', ['purchaseRequest' => $purchaseRequest, 'group' => $itemGroup?->id]) }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition">
								Back to Preview
							</a>
							<x-primary-button>Create All Purchase Orders</x-primary-button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>

	@push('scripts')
	<script>
		document.addEventListener('DOMContentLoaded', function() {
			const applyToAllCheckbox = document.getElementById('applyToAll');
			const firstPoDiv = document.querySelector('[data-po-index="0"]');
			
			if (!firstPoDiv) return;

			const fieldClasses = [
				'funds-cluster-input',
				'funds-available-input',
				'ors-burs-no-input',
				'ors-burs-date-input',
				'delivery-address-input',
				'delivery-date-input',
				'terms-conditions-input',
				'special-instructions-input'
			];

			applyToAllCheckbox.addEventListener('change', function() {
				if (this.checked) {
					fieldClasses.forEach(fieldClass => {
						const firstInput = firstPoDiv.querySelector('.' + fieldClass);
						if (!firstInput) return;

						const allInputs = document.querySelectorAll('.' + fieldClass);
						allInputs.forEach((input, index) => {
							if (index > 0) {
								input.value = firstInput.value;
								input.readOnly = true;
								input.classList.add('bg-gray-100');
							}
						});

						firstInput.addEventListener('input', function() {
							const allInputs = document.querySelectorAll('.' + fieldClass);
							allInputs.forEach((input, index) => {
								if (index > 0) {
									input.value = this.value;
								}
							});
						});
					});
				} else {
					fieldClasses.forEach(fieldClass => {
						const allInputs = document.querySelectorAll('.' + fieldClass);
						allInputs.forEach((input, index) => {
							if (index > 0) {
								input.readOnly = false;
								input.classList.remove('bg-gray-100');
							}
						});
					});
				}
			});
		});
	</script>
	@endpush
</x-app-layout>
