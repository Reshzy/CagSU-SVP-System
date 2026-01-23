@section('title', 'Accounting - Voucher Details')

<x-app-layout>
	<x-slot name="header">
		<h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('Voucher: ') . $voucher->voucher_number }}</h2>
	</x-slot>

	<div class="py-8">
		<div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
			<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
				<div class="p-6 text-gray-900 space-y-6">
					<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
						<div>
							<div class="text-sm text-gray-600">Voucher Number</div>
							<div class="font-medium">{{ $voucher->voucher_number }}</div>
						</div>
						<div>
							<div class="text-sm text-gray-600">Voucher Date</div>
							<div class="font-medium">{{ optional($voucher->voucher_date)->format('Y-m-d') }}</div>
						</div>
						<div>
							<div class="text-sm text-gray-600">PO Number</div>
							<div class="font-medium">{{ $voucher->purchaseOrder?->po_number }}</div>
						</div>
						<div>
							<div class="text-sm text-gray-600">Supplier</div>
							<div class="font-medium">{{ $voucher->supplier?->business_name }}</div>
						</div>
						<div>
							<div class="text-sm text-gray-600">Amount</div>
							<div class="font-medium">₱{{ number_format((float)$voucher->amount, 2) }}</div>
						</div>
						<div>
							<div class="text-sm text-gray-600">Status</div>
							<div class="font-medium capitalize">{{ str_replace('_',' ',$voucher->status) }}</div>
						</div>
					</div>

					<div>
						<div class="text-sm text-gray-600">Remarks</div>
						<div class="whitespace-pre-wrap">{{ $voucher->remarks ?? '—' }}</div>
					</div>

					<div class="flex justify-end">
						<form action="{{ route('accounting.vouchers.update', $voucher) }}" method="POST" class="inline-flex items-center space-x-2 mr-3">
							@csrf
							@method('PUT')
							<select name="action" class="border-gray-300 rounded-md">
								<option value="approve">Approve</option>
								<option value="release">Release</option>
								<option value="mark_paid">Mark Paid</option>
								<option value="cancel">Cancel</option>
							</select>
							<input type="text" name="remarks" placeholder="Remarks" class="border-gray-300 rounded-md" />
							<x-primary-button>Apply</x-primary-button>
						</form>
						<a href="{{ route('accounting.vouchers.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md">Back</a>
					</div>
				</div>
			</div>
		</div>
	</div>
</x-app-layout>


