@section('title', 'Accounting - Create Voucher')

<x-app-layout>
	<x-slot name="header">
		<h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('Create Disbursement Voucher for ') . $purchaseOrder->po_number }}</h2>
	</x-slot>

	<div class="py-8">
		<div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
			<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
				<div class="p-6 text-gray-900">
					<form action="{{ route('accounting.vouchers.store', $purchaseOrder) }}" method="POST" class="space-y-6">
						@csrf
						<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
							<div>
								<label class="text-sm text-gray-600">Voucher Date</label>
								<input type="date" name="voucher_date" class="mt-1 block w-full border-gray-300 rounded-md" required />
							</div>
							<div>
								<label class="text-sm text-gray-600">Amount</label>
								<input type="number" step="0.01" name="amount" class="mt-1 block w-full border-gray-300 rounded-md" value="{{ $purchaseOrder->total_amount }}" required />
							</div>
						</div>

						<div>
							<label class="text-sm text-gray-600">Remarks</label>
							<textarea name="remarks" rows="3" class="mt-1 block w-full border-gray-300 rounded-md"></textarea>
						</div>

						<div class="flex justify-end space-x-3">
							<a href="{{ route('accounting.vouchers.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md">Cancel</a>
							<x-primary-button>Create Voucher</x-primary-button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</x-app-layout>


