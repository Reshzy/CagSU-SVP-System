@section('title', 'BAC - Schedule Meeting')

<x-app-layout>
	<x-slot name="header">
		<h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('Schedule BAC Meeting') }}</h2>
	</x-slot>

	<div class="py-8">
		<div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
			<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
				<div class="p-6 text-gray-900">
					<form action="{{ route('bac.meetings.store') }}" method="POST" class="space-y-6">
						@csrf

						<div>
							<label class="text-sm text-gray-600">Linked Purchase Request (optional)</label>
							<select name="purchase_request_id" class="mt-1 block w-full border-gray-300 rounded-md">
								<option value="">None</option>
								@foreach($purchaseRequests as $pr)
									<option value="{{ $pr->id }}">{{ $pr->pr_number }} - {{ Str::limit($pr->purpose, 60) }}</option>
								@endforeach
							</select>
						</div>

						<div>
							<label class="text-sm text-gray-600">Meeting Date & Time</label>
							<input type="datetime-local" name="meeting_datetime" class="mt-1 block w-full border-gray-300 rounded-md" required />
						</div>

						<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
							<div>
								<label class="text-sm text-gray-600">Title</label>
								<input type="text" name="title" class="mt-1 block w-full border-gray-300 rounded-md" />
							</div>
							<div>
								<label class="text-sm text-gray-600">Location</label>
								<input type="text" name="location" class="mt-1 block w-full border-gray-300 rounded-md" />
							</div>
						</div>

						<div>
							<label class="text-sm text-gray-600">Agenda</label>
							<textarea name="agenda" rows="5" class="mt-1 block w-full border-gray-300 rounded-md"></textarea>
						</div>

						<div class="flex justify-end space-x-3">
							<a href="{{ route('bac.meetings.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md">Cancel</a>
							<x-primary-button>Save</x-primary-button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</x-app-layout>


