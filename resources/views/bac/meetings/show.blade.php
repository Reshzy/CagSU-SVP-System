@section('title', 'BAC - Meeting Details')

<x-app-layout>
	<x-slot name="header">
		<h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('Meeting Details') }}</h2>
	</x-slot>

	<div class="py-8">
		<div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
			<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
				<div class="p-6 text-gray-900 space-y-6">
					<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
						<div>
							<div class="text-sm text-gray-600">When</div>
							<div class="font-medium">{{ $meeting->meeting_datetime->format('Y-m-d H:i') }}</div>
						</div>
						<div>
							<div class="text-sm text-gray-600">Status</div>
							<div class="font-medium capitalize">{{ $meeting->status }}</div>
						</div>
						<div>
							<div class="text-sm text-gray-600">Title</div>
							<div class="font-medium">{{ $meeting->title ?? 'BAC Meeting' }}</div>
						</div>
						<div>
							<div class="text-sm text-gray-600">Location</div>
							<div class="font-medium">{{ $meeting->location ?? '—' }}</div>
						</div>
					</div>

					<div>
						<div class="text-sm text-gray-600">Linked Purchase Request</div>
						<div class="font-medium">{{ $meeting->purchaseRequest?->pr_number ?? '—' }}</div>
					</div>

					<div>
						<div class="text-sm text-gray-600">Agenda</div>
						<div class="whitespace-pre-wrap">{{ $meeting->agenda ?? '—' }}</div>
					</div>

					<div>
						<div class="text-sm text-gray-600">Minutes</div>
						<div class="whitespace-pre-wrap">{{ $meeting->minutes ?? '—' }}</div>
					</div>

					<div>
						<a href="{{ route('bac.meetings.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md">Back</a>
					</div>
				</div>
			</div>
		</div>
	</div>
</x-app-layout>


