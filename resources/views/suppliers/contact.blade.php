@section('title', 'Contact Supply Office')

<x-guest-layout>
	<div class="max-w-xl mx-auto py-12">
		<div class="bg-white shadow rounded-lg p-6">
			<h1 class="text-2xl font-semibold mb-4">Contact Supply Office</h1>
			@if(session('status'))
				<div class="mb-4 p-3 rounded-md bg-green-50 text-green-700">{{ session('status') }}</div>
			@endif
			<form action="{{ route('suppliers.contact.store') }}" method="POST" class="space-y-4">
				@csrf
				<div>
					<label class="text-sm text-gray-600">Related PR Number (optional)</label>
					<input type="text" name="pr_number" class="mt-1 block w-full border-gray-300 rounded-md" placeholder="PR-0126-0001" />
					<x-input-error :messages="$errors->get('pr_number')" class="mt-1" />
				</div>
				<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
					<div>
						<label class="text-sm text-gray-600">Your Name</label>
						<input type="text" name="supplier_name" class="mt-1 block w-full border-gray-300 rounded-md" />
						<x-input-error :messages="$errors->get('supplier_name')" class="mt-1" />
					</div>
					<div>
						<label class="text-sm text-gray-600">Your Email</label>
						<input type="email" name="supplier_email" class="mt-1 block w-full border-gray-300 rounded-md" required />
						<x-input-error :messages="$errors->get('supplier_email')" class="mt-1" />
					</div>
				</div>
				<div>
					<label class="text-sm text-gray-600">Subject</label>
					<input type="text" name="subject" class="mt-1 block w-full border-gray-300 rounded-md" required />
					<x-input-error :messages="$errors->get('subject')" class="mt-1" />
				</div>
				<div>
					<label class="text-sm text-gray-600">Message</label>
					<textarea name="message_body" rows="5" class="mt-1 block w-full border-gray-300 rounded-md" required></textarea>
					<x-input-error :messages="$errors->get('message_body')" class="mt-1" />
				</div>
				<div class="flex justify-end">
					<x-primary-button>Send Message</x-primary-button>
				</div>
			</form>
		</div>
	</div>
</x-guest-layout>


