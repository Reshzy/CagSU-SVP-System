@section('title', 'Supply - Edit Purchase Order')

<x-app-layout>
	<x-slot name="header">
		<h2 class="font-semibold text-2xl text-gray-800 leading-tight">
			{{ __('Edit Purchase Order: ') . $purchaseOrder->po_number }}
			@if(isset($itemGroup))
				<span class="text-lg text-gray-600"> - {{ $itemGroup->group_code }}: {{ $itemGroup->group_name }}</span>
			@endif
		</h2>
	</x-slot>

	<div class="py-8">
		<div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
			<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
				<div class="p-6 text-gray-900">
					@if(isset($itemGroup))
						<div class="mb-4 p-4 rounded-md bg-blue-50 border border-blue-200">
							<div class="flex items-start">
								<svg class="w-5 h-5 text-blue-600 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
									<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
								</svg>
								<div class="flex-1">
									<h4 class="text-sm font-semibold text-blue-900 mb-1">Editing PO for Item Group</h4>
									<p class="text-sm text-blue-800">
										<span class="font-medium">{{ $itemGroup->group_code }}: {{ $itemGroup->group_name }}</span>
										<br>
										<span class="text-xs">{{ $itemGroup->items->count() }} items | Est. Total: ₱{{ number_format($itemGroup->calculateTotalCost(), 2) }}</span>
									</p>
								</div>
							</div>
						</div>
					@endif

					<form action="{{ route('supply.purchase-orders.update', $purchaseOrder) }}" method="POST" class="space-y-6">
						@csrf
						@method('PUT')
						<input type="hidden" name="action" value="edit" />

						<!-- Section 1: Auto-Generated Information -->
						<div class="border-b pb-4">
							<h3 class="text-lg font-semibold mb-3">Purchase Order Information</h3>
							<div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-gray-50 p-4 rounded-md">
								<div>
									<label class="block text-sm font-medium text-gray-600">P.O. Number</label>
									<div class="mt-1 text-base font-semibold text-gray-800">{{ $purchaseOrder->po_number }}</div>
								</div>
								<div>
									<label class="block text-sm font-medium text-gray-600">Date</label>
									<div class="mt-1 text-base font-semibold text-gray-800">{{ $purchaseOrder->po_date->format('F d, Y') }}</div>
								</div>
								<div>
									<label class="block text-sm font-medium text-gray-600">Status</label>
									<div class="mt-1">
										<span class="px-2 py-1 text-xs font-semibold rounded-full 
											@if($purchaseOrder->status === 'completed') bg-green-100 text-green-800
											@elseif($purchaseOrder->status === 'delivered') bg-blue-100 text-blue-800
											@elseif($purchaseOrder->status === 'sent_to_supplier') bg-yellow-100 text-yellow-800
											@else bg-gray-100 text-gray-800
											@endif">
											{{ str_replace('_', ' ', ucfirst($purchaseOrder->status)) }}
										</span>
									</div>
								</div>
							</div>
						</div>

						<!-- Section 2: Supplier Information -->
						<div class="border-b pb-4">
							<h3 class="text-lg font-semibold mb-3">Supplier Information</h3>
							<div class="space-y-4">
								<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
									<div>
										<label class="block text-sm font-medium text-gray-600">Supplier <span class="text-red-500">*</span></label>
										<select name="supplier_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
											@foreach($suppliers as $supplier)
												<option value="{{ $supplier->id }}" {{ $purchaseOrder->supplier_id == $supplier->id ? 'selected' : '' }}>
													{{ $supplier->business_name }}
												</option>
											@endforeach
										</select>
										@error('supplier_id')
											<p class="mt-1 text-sm text-red-600">{{ $message }}</p>
										@enderror
									</div>
									<div>
										<label class="block text-sm font-medium text-gray-600">Supplier Name Override (optional)</label>
										<input 
											type="text" 
											name="supplier_name_override" 
											class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
											value="{{ old('supplier_name_override', $purchaseOrder->supplier_name_override) }}"
											placeholder="Leave blank to use supplier name"
										/>
									</div>
								</div>

								<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
									<div>
										<label class="block text-sm font-medium text-gray-600">Address</label>
										<div class="mt-1 p-2 bg-gray-100 border border-gray-300 rounded-md text-gray-800">
											{{ $purchaseOrder->supplier?->address ?? 'No address available' }}
										</div>
									</div>
									<div>
										<label class="block text-sm font-medium text-gray-600">TIN (optional)</label>
										<input 
											type="text" 
											name="tin" 
											class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
											value="{{ old('tin', $purchaseOrder->tin) }}"
											placeholder="Enter TIN if available"
										/>
									</div>
								</div>
							</div>
						</div>

						<!-- Section 3: Financial Details -->
						<div class="border-b pb-4">
							<h3 class="text-lg font-semibold mb-3">Financial Details</h3>
							<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
								<div>
									<label class="block text-sm font-medium text-gray-600">Funds Cluster <span class="text-red-500">*</span></label>
									<input 
										type="text" 
										name="funds_cluster" 
										class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
										value="{{ old('funds_cluster', $purchaseOrder->funds_cluster) }}"
										required 
									/>
									@error('funds_cluster')
										<p class="mt-1 text-sm text-red-600">{{ $message }}</p>
									@enderror
								</div>
								<div>
									<label class="block text-sm font-medium text-gray-600">Funds Available <span class="text-red-500">*</span></label>
									<input 
										type="number" 
										step="0.01" 
										name="funds_available" 
										class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
										value="{{ old('funds_available', $purchaseOrder->funds_available) }}"
										required 
									/>
									@error('funds_available')
										<p class="mt-1 text-sm text-red-600">{{ $message }}</p>
									@enderror
								</div>
								<div>
									<label class="block text-sm font-medium text-gray-600">ORS/BURS No. <span class="text-red-500">*</span></label>
									<input 
										type="text" 
										name="ors_burs_no" 
										class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
										value="{{ old('ors_burs_no', $purchaseOrder->ors_burs_no) }}"
										required 
									/>
									@error('ors_burs_no')
										<p class="mt-1 text-sm text-red-600">{{ $message }}</p>
									@enderror
								</div>
								<div>
									<label class="block text-sm font-medium text-gray-600">Date of ORS/BURS <span class="text-red-500">*</span></label>
									<input 
										type="date" 
										name="ors_burs_date" 
										class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
										value="{{ old('ors_burs_date', $purchaseOrder->ors_burs_date?->format('Y-m-d')) }}"
										required 
									/>
									@error('ors_burs_date')
										<p class="mt-1 text-sm text-red-600">{{ $message }}</p>
									@enderror
								</div>
								<div>
									<label class="block text-sm font-medium text-gray-600">Total Amount <span class="text-red-500">*</span></label>
									<input 
										type="number" 
										step="0.01" 
										name="total_amount" 
										class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
										value="{{ old('total_amount', $purchaseOrder->total_amount) }}"
										required 
									/>
									@error('total_amount')
										<p class="mt-1 text-sm text-red-600">{{ $message }}</p>
									@enderror
								</div>
							</div>
						</div>

						<!-- Section 4: Delivery Details -->
						<div class="border-b pb-4">
							<h3 class="text-lg font-semibold mb-3">Delivery Details</h3>
							<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
								<div>
									<label class="block text-sm font-medium text-gray-600">Delivery Address <span class="text-red-500">*</span></label>
									<textarea 
										name="delivery_address" 
										rows="2" 
										class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
										required
									>{{ old('delivery_address', $purchaseOrder->delivery_address) }}</textarea>
									@error('delivery_address')
										<p class="mt-1 text-sm text-red-600">{{ $message }}</p>
									@enderror
								</div>
								<div>
									<label class="block text-sm font-medium text-gray-600">Delivery Date Required <span class="text-red-500">*</span></label>
									<input 
										type="date" 
										name="delivery_date_required" 
										class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
										value="{{ old('delivery_date_required', $purchaseOrder->delivery_date_required?->format('Y-m-d')) }}"
										required 
									/>
									@error('delivery_date_required')
										<p class="mt-1 text-sm text-red-600">{{ $message }}</p>
									@enderror
								</div>
							</div>
						</div>

						<!-- Section 5: Signatories -->
						<div class="border-b pb-4">
							<h3 class="text-lg font-semibold mb-3">Signatories</h3>
							<div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-blue-50 p-4 rounded-md">
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

						<!-- Section 6: Additional Notes -->
						<div>
							<h3 class="text-lg font-semibold mb-3">Additional Notes</h3>
							<div class="space-y-4">
								<div>
									<label class="block text-sm font-medium text-gray-600">Terms and Conditions <span class="text-red-500">*</span></label>
									<textarea 
										name="terms_and_conditions" 
										rows="4" 
										class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
										required
									>{{ old('terms_and_conditions', $purchaseOrder->terms_and_conditions) }}</textarea>
									@error('terms_and_conditions')
										<p class="mt-1 text-sm text-red-600">{{ $message }}</p>
									@enderror
								</div>

								<div>
									<label class="block text-sm font-medium text-gray-600">Special Instructions (optional)</label>
									<textarea 
										name="special_instructions" 
										rows="2" 
										class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
									>{{ old('special_instructions', $purchaseOrder->special_instructions) }}</textarea>
								</div>
							</div>
						</div>

						<!-- Submit Buttons -->
						<div class="flex justify-end gap-3 pt-4">
							<a href="{{ route('supply.purchase-orders.show', $purchaseOrder) }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">Cancel</a>
							<x-primary-button>Update Purchase Order</x-primary-button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</x-app-layout>
