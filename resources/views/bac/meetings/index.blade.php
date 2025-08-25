@section('title', 'BAC - Meetings')

<x-app-layout>
	<x-slot name="header">
		<h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('BAC Meetings') }}</h2>
	</x-slot>

	<div class="py-8">
		<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
				<div class="p-6 text-gray-900">
					<div class="flex items-center justify-between mb-4">
						<div class="text-lg font-semibold">Scheduled Meetings</div>
						<a href="{{ route('bac.meetings.create') }}" class="px-3 py-2 bg-cagsu-maroon text-white rounded-md">Schedule Meeting</a>
					</div>

					@if(session('status'))
						<div class="mb-4 p-3 rounded-md bg-green-50 text-green-700">{{ session('status') }}</div>
					@endif

					<div class="overflow-x-auto">
						<table class="min-w-full divide-y divide-gray-200">
							<thead class="bg-gray-50">
								<tr>
									<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">When</th>
									<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
									<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Linked PR</th>
									<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
									<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
									<th class="px-4 py-2"></th>
								</tr>
							</thead>
							<tbody class="bg-white divide-y divide-gray-200">
								@forelse($meetings as $m)
								<tr>
									<td class="px-4 py-2">{{ $m->meeting_datetime->format('Y-m-d H:i') }}</td>
									<td class="px-4 py-2">{{ $m->title ?? 'BAC Meeting' }}</td>
									<td class="px-4 py-2">{{ $m->purchaseRequest?->pr_number ?? '—' }}</td>
									<td class="px-4 py-2">{{ $m->location ?? '—' }}</td>
									<td class="px-4 py-2 capitalize">{{ $m->status }}</td>
									<td class="px-4 py-2 text-right">
										<a href="{{ route('bac.meetings.show', $m) }}" class="px-3 py-2 bg-gray-100 text-gray-700 rounded-md">Details</a>
									</td>
								</tr>
								@empty
								<tr>
									<td colspan="6" class="px-4 py-6 text-center text-gray-500">No meetings scheduled.</td>
								</tr>
								@endforelse
							</tbody>
						</table>
					</div>

					<div class="mt-4">{{ $meetings->links() }}</div>
				</div>
			</div>
		</div>
	</div>
</x-app-layout>


