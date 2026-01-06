@section('title', 'Submit Quotation')

<x-guest-layout>
	<div class="max-w-xl mx-auto py-12">
		<div class="bg-white shadow rounded-lg p-6">
			<h1 class="text-2xl font-semibold mb-4">Submit Quotation</h1>
			@if(session('status'))
				<div class="mb-4 p-3 rounded-md bg-green-50 text-green-700">{{ session('status') }}</div>
			@endif
			<form action="{{ route('suppliers.quotations.submit.store') }}" method="POST" class="space-y-4">
				@csrf
				<div>
					<label class="text-sm text-gray-600">PR Number</label>
					<input type="text" name="pr_number" class="mt-1 block w-full border-gray-300 rounded-md" placeholder="PR-0126-0001" required />
					<x-input-error :messages="$errors->get('pr_number')" class="mt-1" />
				</div>
				<div>
					<label class="text-sm text-gray-600">Supplier Email</label>
					<input type="email" name="supplier_email" class="mt-1 block w-full border-gray-300 rounded-md" required />
					<x-input-error :messages="$errors->get('supplier_email')" class="mt-1" />
				</div>
				<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
					<div>
						<label class="text-sm text-gray-600">Quotation Date</label>
						<input type="date" name="quotation_date" class="mt-1 block w-full border-gray-300 rounded-md" required />
						<x-input-error :messages="$errors->get('quotation_date')" class="mt-1" />
					</div>
					<div>
						<label class="text-sm text-gray-600">Validity Date</label>
						<input type="date" name="validity_date" class="mt-1 block w-full border-gray-300 rounded-md" required />
						<x-input-error :messages="$errors->get('validity_date')" class="mt-1" />
					</div>
				</div>
				<div>
					<label class="text-sm text-gray-600">Total Amount</label>
					<input type="number" step="0.01" name="total_amount" class="mt-1 block w-full border-gray-300 rounded-md" required />
					<x-input-error :messages="$errors->get('total_amount')" class="mt-1" />
				</div>
				<div class="flex justify-end">
					<x-primary-button>Submit Quotation</x-primary-button>
				</div>
			</form>
		</div>
	</div>
</x-guest-layout>


