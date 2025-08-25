@section('title', 'Edit Supplier')

<x-app-layout>
	<x-slot name="header">
		<h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('Edit Supplier') }}</h2>
	</x-slot>

	<div class="py-8">
		<div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
			<div class="bg-white shadow rounded-lg p-6">
				@if(session('status'))
					<div class="mb-4 p-3 rounded-md bg-green-50 text-green-700">{{ session('status') }}</div>
				@endif
				<form action="{{ route('supply.suppliers.update', $supplier) }}" method="POST" class="space-y-4">
					@csrf
					@method('PUT')
					<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
						<div>
							<x-input-label value="Business Name" />
							<x-text-input type="text" name="business_name" value="{{ old('business_name', $supplier->business_name) }}" class="mt-1 block w-full" required />
							<x-input-error :messages="$errors->get('business_name')" class="mt-1" />
						</div>
						<div>
							<x-input-label value="Contact Person" />
							<x-text-input type="text" name="contact_person" value="{{ old('contact_person', $supplier->contact_person) }}" class="mt-1 block w-full" required />
							<x-input-error :messages="$errors->get('contact_person')" class="mt-1" />
						</div>
						<div>
							<x-input-label value="Email" />
							<x-text-input type="email" name="email" value="{{ old('email', $supplier->email) }}" class="mt-1 block w-full" required />
							<x-input-error :messages="$errors->get('email')" class="mt-1" />
						</div>
						<div>
							<x-input-label value="Phone" />
							<x-text-input type="text" name="phone" value="{{ old('phone', $supplier->phone) }}" class="mt-1 block w-full" required />
							<x-input-error :messages="$errors->get('phone')" class="mt-1" />
						</div>
						<div class="md:col-span-2">
							<x-input-label value="Address" />
							<textarea name="address" rows="2" class="mt-1 block w-full border-gray-300 rounded-md" required>{{ old('address', $supplier->address) }}</textarea>
							<x-input-error :messages="$errors->get('address')" class="mt-1" />
						</div>
						<div>
							<x-input-label value="City" />
							<x-text-input type="text" name="city" value="{{ old('city', $supplier->city) }}" class="mt-1 block w-full" required />
							<x-input-error :messages="$errors->get('city')" class="mt-1" />
						</div>
						<div>
							<x-input-label value="Province" />
							<x-text-input type="text" name="province" value="{{ old('province', $supplier->province) }}" class="mt-1 block w-full" required />
							<x-input-error :messages="$errors->get('province')" class="mt-1" />
						</div>
						<div class="md:col-span-2">
							<x-input-label value="Business Type" />
							<select name="business_type" class="mt-1 block w-full border-gray-300 rounded-md" required>
								@php($types=['sole_proprietorship'=>'Sole Proprietorship','partnership'=>'Partnership','corporation'=>'Corporation','cooperative'=>'Cooperative'])
								@foreach($types as $val=>$label)
									<option value="{{ $val }}" @selected(old('business_type', $supplier->business_type)===$val)>{{ $label }}</option>
								@endforeach
							</select>
							<x-input-error :messages="$errors->get('business_type')" class="mt-1" />
						</div>
						<div>
							<x-input-label value="Status" />
							<select name="status" class="mt-1 block w-full border-gray-300 rounded-md" required>
								@php($statuses=['active'=>'Active','inactive'=>'Inactive','blacklisted'=>'Blacklisted','pending_verification'=>'Pending Verification'])
								@foreach($statuses as $val=>$label)
									<option value="{{ $val }}" @selected(old('status', $supplier->status)===$val)>{{ $label }}</option>
								@endforeach
							</select>
							<x-input-error :messages="$errors->get('status')" class="mt-1" />
						</div>
					</div>

					<div class="flex justify-end gap-2">
						<a href="{{ route('supply.suppliers.index') }}" class="px-4 py-2 bg-gray-100 rounded-md">Cancel</a>
						<x-primary-button>Save Changes</x-primary-button>
					</div>
				</form>
			</div>
		</div>
	</div>
</x-app-layout>


