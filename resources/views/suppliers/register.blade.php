@section('title', 'Supplier Registration')

<x-guest-layout>
	<div class="max-w-2xl mx-auto py-12">
		<div class="bg-white shadow rounded-lg p-6">
			<h1 class="text-2xl font-semibold mb-4">Supplier Registration</h1>
			@if(session('status'))
				<div class="mb-4 p-3 rounded-md bg-green-50 text-green-700">{{ session('status') }}</div>
			@endif
			<form action="{{ route('suppliers.register.store') }}" method="POST" class="space-y-4">
				@csrf
				<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
					<div>
						<label class="text-sm text-gray-600">Business Name</label>
						<input type="text" name="business_name" class="mt-1 block w-full border-gray-300 rounded-md" required />
						<x-input-error :messages="$errors->get('business_name')" class="mt-1" />
					</div>
					<div>
						<label class="text-sm text-gray-600">Contact Person</label>
						<input type="text" name="contact_person" class="mt-1 block w-full border-gray-300 rounded-md" required />
						<x-input-error :messages="$errors->get('contact_person')" class="mt-1" />
					</div>
					<div>
						<label class="text-sm text-gray-600">Email</label>
						<input type="email" name="email" class="mt-1 block w-full border-gray-300 rounded-md" required />
						<x-input-error :messages="$errors->get('email')" class="mt-1" />
					</div>
					<div>
						<label class="text-sm text-gray-600">Phone</label>
						<input type="text" name="phone" class="mt-1 block w-full border-gray-300 rounded-md" required />
						<x-input-error :messages="$errors->get('phone')" class="mt-1" />
					</div>
					<div class="md:col-span-2">
						<label class="text-sm text-gray-600">Address</label>
						<textarea name="address" rows="2" class="mt-1 block w-full border-gray-300 rounded-md" required></textarea>
						<x-input-error :messages="$errors->get('address')" class="mt-1" />
					</div>
					<div>
						<label class="text-sm text-gray-600">City</label>
						<input type="text" name="city" class="mt-1 block w-full border-gray-300 rounded-md" required />
						<x-input-error :messages="$errors->get('city')" class="mt-1" />
					</div>
					<div>
						<label class="text-sm text-gray-600">Province</label>
						<input type="text" name="province" class="mt-1 block w-full border-gray-300 rounded-md" required />
						<x-input-error :messages="$errors->get('province')" class="mt-1" />
					</div>
					<div class="md:col-span-2">
						<label class="text-sm text-gray-600">Business Type</label>
						<select name="business_type" class="mt-1 block w-full border-gray-300 rounded-md" required>
							<option value="sole_proprietorship">Sole Proprietorship</option>
							<option value="partnership">Partnership</option>
							<option value="corporation">Corporation</option>
							<option value="cooperative">Cooperative</option>
						</select>
						<x-input-error :messages="$errors->get('business_type')" class="mt-1" />
					</div>
				</div>

				<div class="flex justify-end">
					<x-primary-button>Submit Registration</x-primary-button>
				</div>
			</form>
		</div>
	</div>
</x-guest-layout>


