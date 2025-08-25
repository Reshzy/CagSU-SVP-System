@section('title', 'Supply - Record Inventory Receipt')

<x-app-layout>
	<x-slot name="header">
		<h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('Record Inventory Receipt for ') . $purchaseOrder->po_number }}</h2>
	</x-slot>

	<div class="py-8">
		<div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
			<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
				<div class="p-6 text-gray-900">
					<form action="{{ route('supply.inventory-receipts.store', $purchaseOrder) }}" method="POST" class="space-y-6">
						@csrf
						<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
							<div>
								<label class="text-sm text-gray-600">Received Date</label>
								<input type="date" name="received_date" class="mt-1 block w-full border-gray-300 rounded-md" required />
							</div>
							<div class="md:col-span-2">
								<label class="text-sm text-gray-600">Reference No.</label>
								<input type="text" name="reference_no" class="mt-1 block w-full border-gray-300 rounded-md" />
							</div>
						</div>

						<div>
							<label class="text-sm text-gray-600">Notes</label>
							<textarea name="notes" rows="3" class="mt-1 block w-full border-gray-300 rounded-md"></textarea>
						</div>

						<div class="border-t pt-4">
							<div class="text-lg font-semibold mb-2">Items</div>
							<div id="items-container" class="space-y-3">
								<div class="grid grid-cols-1 md:grid-cols-4 gap-2">
									<input type="text" name="items[0][description]" placeholder="Description" class="border-gray-300 rounded-md md:col-span-2" required />
									<input type="text" name="items[0][unit_of_measure]" placeholder="Unit" class="border-gray-300 rounded-md" />
									<input type="number" step="0.01" name="items[0][quantity]" placeholder="Qty" class="border-gray-300 rounded-md" required />
								</div>
							</div>
						</div>

						<div class="flex justify-end space-x-3">
							<a href="{{ route('supply.inventory-receipts.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md">Cancel</a>
							<x-primary-button>Save Receipt</x-primary-button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</x-app-layout>


